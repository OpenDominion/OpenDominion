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
        DB::beginTransaction();
        Round::query()->whereKey($this->round->id)->sharedLock()->firstOrFail();
        $first = $this->startWorker('tick');
        $second = $this->startWorker('tick');
        $this->releaseWorker($first);
        $this->releaseWorker($second);
        $this->awaitRoundLock($first, 'for update');
        $this->awaitRoundLock($second, 'for update');
        $this->assertSame($before, $this->dominion->fresh()->resource_platinum);
        DB::commit();

        $applied = [$this->finishWorker($first)['applied'], $this->finishWorker($second)['applied']];
        sort($applied);
        $this->assertSame([false, true], $applied);
        $this->assertSame($before + $production, $this->dominion->fresh()->resource_platinum);
        $this->assertSingleCommittedHour();
    }

    public function testPlayerActionHoldsTheRoundUntilItsChangesAreCommitted(): void
    {
        $before = $this->dominion->resource_platinum;
        $production = $this->dominion->tick->resource_platinum;
        $action = $this->startWorker('action-hold');
        $this->releaseWorker($action);
        $this->awaitStage($action, 'locked');
        $tick = $this->startWorker('tick');
        $this->releaseWorker($tick);
        $this->awaitRoundLock($tick, 'for update');
        $this->assertSame($before, $this->dominion->fresh()->resource_platinum);
        $this->assertNull(RoundTickRun::query()->where('round_id', $this->round->id)->value('completed_at'));
        $this->releaseWorker($action);

        $this->assertSame($before, $this->finishWorker($action)['observed_platinum']);
        $this->assertTrue($this->finishWorker($tick)['applied']);
        $this->assertSame($before + 17 + $production, $this->dominion->fresh()->resource_platinum);
        $this->assertSingleCommittedHour();
    }

    public function testPlayerActionWaitsForTickAndRefreshesItsStaleModel(): void
    {
        $before = $this->dominion->resource_platinum;
        $production = $this->dominion->tick->resource_platinum;
        $action = $this->startWorker('action');
        $tick = $this->startWorker('tick-hold');
        $this->releaseWorker($tick);
        $this->awaitStage($tick, 'locked');
        $this->releaseWorker($action);
        $this->awaitRoundLock($action, 'lock in share mode');
        $this->assertSame($before, $this->dominion->fresh()->resource_platinum);
        $this->releaseWorker($tick);

        $this->assertTrue($this->finishWorker($tick)['applied']);
        $result = $this->finishWorker($action);
        $this->assertSame($before, $result['initial_platinum']);
        $this->assertSame($before + $production, $result['observed_platinum']);
        $this->assertSame($before + $production + 17, $this->dominion->fresh()->resource_platinum);
        $this->assertSingleCommittedHour();
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

    protected function startWorker(string $mode): int
    {
        $input = new InputStream();
        $connection = DB::connection()->getConfig();
        $connection['options'] = [];
        $process = new Process([
            PHP_BINARY, base_path('tests/Fixtures/tick-concurrency-worker.php'),
            $mode, (string) $this->round->id, (string) $this->dominion->id,
            now()->toDateTimeString(),
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

    protected function awaitRoundLock(int $index, string $lockClause): void
    {
        $connectionId = $this->awaitStage($index, 'ready')['connection_id'];
        $this->awaitStage($index, 'attempting');
        $deadline = microtime(true) + 10;
        do {
            foreach (DB::select('SHOW FULL PROCESSLIST') as $process) {
                $sql = strtolower($process->Info ?? '');
                if ((int) $process->Id === $connectionId && str_contains($sql, 'from `rounds`') && str_contains($sql, $lockClause)) {
                    $this->assertTrue($this->workers[$index]['process']->isRunning());
                    return;
                }
            }
            if (!$this->workers[$index]['process']->isRunning()) {
                $this->fail('Worker finished without waiting for the round lock: ' . $this->workers[$index]['process']->getOutput());
            }
            usleep(1000);
        } while (microtime(true) < $deadline);
        $this->fail('Worker did not wait for the expected round lock.');
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
        $this->assertSame(20, $this->dominion->fresh()->building_home);
        $this->assertFalse($this->dominion->queues()->where('source', 'construction')->exists());
    }

    protected function snapshot(): array
    {
        $snapshot = ['dominion' => (array) DB::table('dominions')->find($this->dominion->id)];
        foreach (['dominion_tick', 'dominion_queue', 'dominion_history', 'notification_outbox'] as $table) {
            $snapshot[$table] = DB::table($table)->where('dominion_id', $this->dominion->id)->get()->toJson();
        }
        return $snapshot;
    }
}
