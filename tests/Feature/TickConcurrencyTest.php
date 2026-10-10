<?php

namespace OpenDominion\Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\NotificationOutbox;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundTickRun;
use OpenDominion\Models\User;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\TickService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

class TickConcurrencyTest extends AbstractBrowserKitTestCase
{
    protected ?Dominion $dominion = null;

    protected ?Round $round = null;

    protected ?Realm $realm = null;

    protected ?User $user = null;

    /** @var array<int, array{process: Process, input: InputStream}> */
    protected array $workers = [];

    protected function setUp(): void
    {
        parent::setUp();
        if (!in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Independent InnoDB connection tests require MySQL or MariaDB.');
        }
        $this->assertSame(0, DB::connection()->transactionLevel(), 'Worker fixtures must be committed.');
        Bus::fake();
        Carbon::setTestNow(now()->startOfHour());
        $this->round = $this->createRound('-7 days');
        $this->realm = $this->createRealm($this->round);
        $this->user = $this->createUser(null, ['email' => Str::uuid() . '@tick-concurrency.invalid']);
        $this->user->update(['settings' => [
            'notifications' => ['hourly_dominion' => ['construction_completed' => ['ingame' => true, 'email' => true]]],
        ]]);
        $this->dominion = $this->createDominionWithLegacyStats($this->user, $this->round, null, $this->realm);
        $this->dominion->update(['resource_food' => 999999, 'resource_mana' => 999999]);
        app(QueueService::class)->queueResources('construction', $this->dominion, ['building_home' => 10], 1);
        app(TickService::class)->precalculateTick($this->dominion->fresh());
        $this->dominion->refresh();
    }

    protected function tearDown(): void
    {
        try {
            foreach ($this->workers as $worker) {
                $worker['process']->stop(0);
                $worker['input']->close();
            }
            if ($this->app !== null) {
                while (DB::connection()->transactionLevel() > 0) {
                    DB::connection()->rollBack();
                }
                if ($this->dominion !== null) {
                    foreach (['notification_outbox', 'dominion_history', 'dominion_tick', 'dominion_queue', 'dominion_spells'] as $table) {
                        DB::table($table)->where('dominion_id', $this->dominion->id)->delete();
                    }
                    $this->dominion->notifications()->delete();
                    Dominion::query()->whereKey($this->dominion->id)->delete();
                }
                $this->realm?->delete();
                $this->round?->delete();
                $this->user?->delete();
            }
        } finally {
            Carbon::setTestNow();
            parent::tearDown();
        }
    }

    public function testDuplicateScheduledWorkersCommitTheHourOnce(): void
    {
        $before = $this->dominion->resource_platinum;
        $production = $this->dominion->tick->resource_platinum;
        $first = $this->startWorker('tick-hold');
        $second = $this->startWorker('tick');
        $this->releaseWorker($first);
        $this->awaitStage($first, 'locked');
        $this->releaseWorker($second);
        $this->awaitDatabaseLock($second, 'round_tick_runs');
        $this->assertSame($before, $this->dominion->fresh()->resource_platinum);
        $this->releaseWorker($first);

        $this->assertTrue($this->finishWorker($first)['applied']);
        $this->assertFalse($this->finishWorker($second)['applied']);
        $this->assertSame($before + $production, $this->dominion->fresh()->resource_platinum);
        $this->assertSingleCommittedHour();
    }

    public function testPlayerRoundRecordUpdateCommitsWhileTickHoldsItsCheckpoint(): void
    {
        $before = $this->dominion->resource_platinum;
        $tick = $this->startWorker('tick-hold');
        $this->releaseWorker($tick);
        $this->awaitStage($tick, 'locked');
        $action = $this->startWorker('round-record-update');
        $this->releaseWorker($action);
        $this->finishWorker($action);

        $this->assertSame(321, (int) $this->round->fresh()->largest_hit);
        $this->assertSame($before, $this->dominion->fresh()->resource_platinum);
        $this->assertNull(RoundTickRun::query()->where('round_id', $this->round->id)->value('completed_at'));
        $this->releaseWorker($tick);
        $this->assertTrue($this->finishWorker($tick)['applied']);
        $this->assertSingleCommittedHour();
    }

    public function testPlayerActionCanUpdateRoundWhileTickWaitsForItsDominion(): void
    {
        $before = $this->dominion->resource_platinum;
        $production = $this->dominion->tick->resource_platinum;
        $action = $this->startWorker('action-hold-before-round-update');
        $this->releaseWorker($action);
        $this->awaitStage($action, 'locked');
        $tick = $this->startWorker('tick');
        $this->releaseWorker($tick);
        $this->awaitDatabaseLock($tick, 'dominions', 'for update');
        $this->releaseWorker($action);

        $this->assertSame($before, $this->finishWorker($action)['observed_platinum']);
        $this->assertTrue($this->finishWorker($tick)['applied']);
        $this->assertSame(321, (int) $this->round->fresh()->largest_hit);
        $this->assertSame($before + 17 + $production, $this->dominion->fresh()->resource_platinum);
        $this->assertSingleCommittedHour();
    }

    public function testOlderWorkerResumingAfterNewerHourDoesNotApplyProduction(): void
    {
        $before = $this->dominion->resource_platinum;
        $production = $this->dominion->tick->resource_platinum;
        $older = $this->startWorker('tick-hold-after-registration');
        $this->releaseWorker($older);
        $this->awaitStage($older, 'registered');
        $newerHour = now()->addHour();
        $newer = $this->startWorker('tick', $newerHour);
        $this->releaseWorker($newer);
        $this->assertTrue($this->finishWorker($newer)['applied']);
        $afterNewer = $this->snapshot();
        $this->releaseWorker($older);

        $this->assertFalse($this->finishWorker($older)['applied']);
        $this->assertSame($afterNewer, $this->snapshot());
        $this->assertSame($before + $production, $this->dominion->fresh()->resource_platinum);
        $run = RoundTickRun::query()->where('round_id', $this->round->id)->sole();
        $this->assertTrue($run->tick_at->equalTo($newerHour));
        $this->assertSingleCommittedHour();
    }

    public function testFirstPageReadSkipsActivityWriteWhileDominionIsLocked(): void
    {
        $this->dominion->update(['hourly_activity' => null]);
        DB::beginTransaction();
        Dominion::query()->whereKey($this->dominion->id)->lockForUpdate()->firstOrFail();
        $reader = $this->startWorker('selector-read');
        $this->releaseWorker($reader);
        $result = $this->finishWorker($reader);

        $this->assertSame($this->dominion->id, $result['dominion_id']);
        $this->assertNull($result['hourly_activity']);
        $this->assertNull($this->dominion->fresh()->hourly_activity);
        DB::commit();

        $retry = $this->startWorker('selector-read');
        $this->releaseWorker($retry);
        $result = $this->finishWorker($retry);
        $this->assertSame('1', $result['hourly_activity'][$this->round->getTick()]);
        $this->assertSame($result['hourly_activity'], $this->dominion->fresh()->hourly_activity);
    }

    public function testProcessFailureAfterCommitDoesNotReapplyTheHour(): void
    {
        $crash = $this->startWorker('tick-crash-after-commit');
        $this->releaseWorker($crash);
        $this->awaitStage($crash, 'committed');
        $this->assertSame(73, $this->workers[$crash]['process']->wait());
        $this->assertSingleCommittedHour();
        $outbox = NotificationOutbox::query()->where('dominion_id', $this->dominion->id)->sole();
        $this->assertNull($outbox->delivered_at);
        $beforeRetry = $this->snapshot();
        $retry = $this->startWorker('tick');
        $this->releaseWorker($retry);

        $this->assertFalse($this->finishWorker($retry)['applied']);
        $this->assertSame($beforeRetry, $this->snapshot());
    }

    protected function startWorker(string $mode, ?Carbon $hour = null): int
    {
        $input = new InputStream();
        $connection = DB::connection()->getConfig();
        $connection['options'] = [];
        $process = new Process([
            PHP_BINARY, base_path('tests/Fixtures/tick-concurrency-worker.php'),
            $mode, (string) $this->round->id, (string) $this->dominion->id,
            ($hour ?? now())->toDateTimeString(),
        ], base_path(), [
            'APP_ENV' => 'testing',
            'OD_TEST_DATABASE_CONFIG' => json_encode($connection, JSON_THROW_ON_ERROR),
        ], $input, 25);
        $index = count($this->workers);
        $this->workers[] = ['process' => $process, 'input' => $input];
        $process->start();
        $this->awaitStage($index, 'ready');
        return $index;
    }

    protected function releaseWorker(int $index): void
    {
        $this->workers[$index]['input']->write("continue\n");
    }

    protected function awaitStage(int $index, string $stage): array
    {
        $process = $this->workers[$index]['process'];
        $deadline = microtime(true) + 15;
        do {
            foreach (explode("\n", trim($process->getOutput())) as $line) {
                $event = json_decode($line, true);
                if (is_array($event) && ($event['stage'] ?? null) === $stage) {
                    return $event;
                }
            }
            if (!$process->isRunning()) {
                $this->fail("Worker exited before {$stage}: " . $process->getOutput() . $process->getErrorOutput());
            }
            usleep(1000);
        } while (microtime(true) < $deadline);
        $this->fail("Worker did not reach {$stage}: " . $process->getOutput() . $process->getErrorOutput());
    }

    protected function awaitDatabaseLock(int $index, string $table, string $lockClause = ''): void
    {
        $connectionId = $this->awaitStage($index, 'ready')['connection_id'];
        $this->awaitStage($index, 'attempting');
        $deadline = microtime(true) + 10;
        do {
            foreach (DB::select('SHOW FULL PROCESSLIST') as $process) {
                $sql = strtolower($process->Info ?? '');
                if ((int) $process->Id === $connectionId && str_contains($sql, "`{$table}`") && str_contains($sql, $lockClause)) {
                    $this->assertTrue($this->workers[$index]['process']->isRunning());
                    return;
                }
            }
            if (!$this->workers[$index]['process']->isRunning()) {
                $this->fail("Worker finished without waiting for {$table}: " . $this->workers[$index]['process']->getOutput() . $this->workers[$index]['process']->getErrorOutput());
            }
            usleep(1000);
        } while (microtime(true) < $deadline);
        $this->fail("Worker did not wait for the expected {$table} lock.");
    }

    protected function finishWorker(int $index): array
    {
        $result = $this->awaitStage($index, 'done');
        $process = $this->workers[$index]['process'];
        $this->assertSame(0, $process->wait(), $process->getErrorOutput());
        return $result;
    }

    protected function assertSingleCommittedHour(): void
    {
        $run = RoundTickRun::query()->where('round_id', $this->round->id)->sole();
        $this->assertNotNull($run->completed_at);
        $this->assertSame(1, $run->attempts);
        $this->assertSame(1, $this->dominion->history()->where('event', 'tick')->count());
        $this->assertSame(1, NotificationOutbox::query()->where('dominion_id', $this->dominion->id)->count());
        $this->assertSame(1, $this->dominion->notifications()->count());
        $this->assertSame(20, $this->dominion->fresh()->building_home);
        $this->assertFalse($this->dominion->queues()->where('source', 'construction')->exists());
    }

    protected function snapshot(): array
    {
        $snapshot = ['dominion' => (array) DB::table('dominions')->find($this->dominion->id)];
        foreach (['dominion_tick', 'dominion_queue', 'dominion_history', 'notification_outbox'] as $table) {
            $snapshot[$table] = DB::table($table)->where('dominion_id', $this->dominion->id)->get()->toJson();
        }
        $snapshot['checkpoint'] = RoundTickRun::query()->where('round_id', $this->round->id)->get()->toJson();
        $snapshot['notifications'] = $this->dominion->notifications()->orderBy('id')->get()->toJson();
        return $snapshot;
    }
}
