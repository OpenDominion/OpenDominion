<?php

namespace OpenDominion\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\RoundTickRun;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\TickService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

class AtomicTickTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();
    }

    protected function prepareDominion(): Dominion
    {
        $dominion = $this->createDominionWithLegacyStats(
            $this->createUser(), $this->createRound('-7 days'),
            Race::where('key', 'dark-elf-rework')->firstOrFail()
        );
        $dominion->update([
            'building_wizard_guild' => 71, 'racial_value' => 0,
            'resource_food' => 999999, 'resource_mana' => 999999,
        ]);
        app(SpellActionService::class)->castSpell($dominion, 'spellwrights_calling');
        return $dominion->fresh();
    }

    protected function snapshot(Dominion $dominion): array
    {
        $snapshot = ['dominion' => (array) DB::table('dominions')->find($dominion->id)];
        foreach (['dominion_tick', 'dominion_queue', 'dominion_spells', 'dominion_history', 'notification_outbox'] as $table) {
            $snapshot[$table] = DB::table($table)->where('dominion_id', $dominion->id)->get()->toJson();
        }
        return $snapshot;
    }

    public static function failureStages(): array
    {
        return [['resources'], ['spell effects'], ['cleanup'], ['prediction']];
    }

    #[DataProvider('failureStages')]
    public function testFailureRollsBackWholeHourAndRetryAppliesItOnce(string $stage): void
    {
        $dominion = $this->prepareDominion();
        $tick = new FaultInjectingTickService();
        $this->app->instance(TickService::class, $tick);
        $tick->failAt = $stage;
        $before = $this->snapshot($dominion);
        $hour = now()->startOfHour();
        try {
            $tick->performTick($dominion->round, null, $hour);
            $this->fail('Expected injected failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected ' . $stage, $exception->getMessage());
        }
        $this->assertSame($before, $this->snapshot($dominion));
        $this->assertFalse($tick->isProcessingTick());
        $run = RoundTickRun::where('round_id', $dominion->round_id)->firstOrFail();
        $this->assertNull($run->completed_at);
        $this->assertSame(1, $run->attempts);
        $tick->failAt = null;
        $this->assertTrue($tick->performTick($dominion->round, null, $hour));
        $this->assertSame(3, (int) DB::table('dominion_queue')->where('dominion_id', $dominion->id)->where('resource', 'military_unit3')->where('hours', 12)->value('amount'));
        $after = $this->snapshot($dominion);
        $this->assertFalse($tick->performTick($dominion->round, null, $hour));
        $this->assertSame($after, $this->snapshot($dominion));
        $this->assertNotNull($run->fresh()->completed_at);
    }

    public function testProtectionNotificationsRollBackWithFailureAndDisappearOnUndo(): void
    {
        Notification::swap(new ChannelManager($this->app));
        $dominion = $this->prepareDominion();
        $dominion->update(['protection_finished' => false]);
        $dominion->user->update(['settings' => [
            'notifications' => ['hourly_dominion' => ['exploration_completed' => ['ingame' => true, 'email' => true]]],
        ]]);
        app(QueueService::class)->queueResources('exploration', $dominion, ['land_plain' => 10], 1);
        $tick = new FaultInjectingTickService();
        $this->app->instance(TickService::class, $tick);
        $tick->precalculateTick($dominion);
        $before = $dominion->notifications()->count();
        $mailAttempts = 0;
        Event::listen(NotificationSending::class, function (NotificationSending $event) use (&$mailAttempts): void {
            if ($event->channel === 'mail') {
                $mailAttempts++;
            }
        });
        $hour = now()->addSeconds(5);
        $tick->failAt = 'prediction';
        try {
            $tick->performTick($dominion->round, $dominion, $hour);
            $this->fail('Expected injected failure after notification creation.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected prediction', $exception->getMessage());
        }
        $this->assertSame($before, $dominion->notifications()->count());
        $tick->failAt = null;
        $this->assertTrue($tick->performTick($dominion->round, $dominion, $hour));
        $this->assertSame($before + 1, $dominion->notifications()->count());
        $this->assertSame(0, $mailAttempts);
        $this->assertSame(0, DB::table('notification_outbox')->where('dominion_id', $dominion->id)->count());
        $this->assertTrue($tick->revertTick($dominion));
        $this->assertSame($before, $dominion->notifications()->count());
    }

    public function testSpellProducesEveryHourIncludingItsFinalHour(): void
    {
        $dominion = $this->prepareDominion();
        $tick = app(TickService::class);
        $hour = now()->startOfHour();
        for ($index = 0; $index < 12; $index++) {
            $this->assertTrue($tick->performTick($dominion->round, null, $hour->copy()->addHours($index)));
            $batches = DB::table('dominion_queue')->where('dominion_id', $dominion->id)->where('resource', 'military_unit3')->pluck('amount', 'hours');
            $this->assertContains((int) $batches[12], [3, 4]);
            $this->assertCount($index + 1, $batches);
        }
        $this->assertSame(42, (int) $batches->sum());
        $this->assertFalse(DB::table('dominion_spells')->where('dominion_id', $dominion->id)->exists());
        $this->assertEqualsWithDelta(0.6, $dominion->fresh()->racial_value, 0.00001);
    }

    public function testHistoryRecordsConsumedPredictionInsteadOfNextHoursArrivals(): void
    {
        $dominion = $this->prepareDominion();
        $queue = app(QueueService::class);
        $queue->queueResources('training', $dominion, ['military_unit3' => 7], 1);
        $queue->queueResources('training', $dominion, ['military_unit3' => 11], 2);
        $tick = app(TickService::class);
        $tick->precalculateTick($dominion);
        $home = $dominion->military_unit3;
        $tick->performTick($dominion->round);
        $this->assertSame($home + 7, $dominion->fresh()->military_unit3);
        $history = $dominion->history()->where('event', 'tick')->orderByDesc('id')->firstOrFail();
        $this->assertSame(7, $history->delta['military_unit3']);
        $this->assertSame(11, (int) $dominion->fresh()->tick->military_unit3);
    }

    public function testAllPhasesUseTheSameEligibleDominions(): void
    {
        $dominion = $this->prepareDominion();
        $dominion->update(['protection_finished' => false, 'protection_ticks_remaining' => 12]);
        $before = $this->snapshot($dominion);
        app(TickService::class)->performTick($dominion->round);
        $this->assertSame($before, $this->snapshot($dominion));
    }

    public function testMissingPredictionAbortsBeforeAnyProgression(): void
    {
        $dominion = $this->prepareDominion();
        DB::table('dominion_tick')->where('dominion_id', $dominion->id)->delete();
        $before = $this->snapshot($dominion);
        try {
            app(TickService::class)->performTick($dominion->round);
            $this->fail('Expected missing prediction failure.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('missing its precalculated tick', $exception->getMessage());
        }
        $this->assertSame($before, $this->snapshot($dominion));
    }

    public function testProtectionCanAdvanceMultipleStepsInOneClockHour(): void
    {
        $dominion = $this->prepareDominion();
        $tick = app(TickService::class);
        $tick->performTick($dominion->round, $dominion);
        $tick->performTick($dominion->round, $dominion);
        $this->assertSame(2, DB::table('dominion_queue')->where('dominion_id', $dominion->id)->where('resource', 'military_unit3')->count());
        $this->assertSame(0, RoundTickRun::where('round_id', $dominion->round_id)->count());
    }

    public function testRecoveryRetainsTheFailedHourAndIncludesLaterHoursInOrder(): void
    {
        $dominion = $this->prepareDominion();
        $hour = now()->startOfHour();
        RoundTickRun::create(['round_id' => $dominion->round_id, 'tick_at' => $hour]);
        $hours = app(TickService::class)->getDueTickHours($dominion->round, $hour->copy()->addHours(2));
        $this->assertEquals([$hour, $hour->copy()->addHour(), $hour->copy()->addHours(2)], $hours);
    }
    public function testCompletedLaterHourMakesAnOlderRequestANoop(): void
    {
        $dominion = $this->prepareDominion();
        $tick = app(TickService::class);
        $hour = now()->startOfHour();
        $tick->performTick($dominion->round, null, $hour);
        $before = $this->snapshot($dominion);
        $this->assertFalse($tick->performTick($dominion->round, null, $hour->copy()->subHour()));
        $this->assertSame($before, $this->snapshot($dominion));
        $this->assertSame(1, RoundTickRun::where('round_id', $dominion->round_id)->count());
    }

    public function testRecoveryStopsBeforeRoundEndAndIncludesUnfinishedFinalHour(): void
    {
        $dominion = $this->prepareDominion();
        $hour = now()->startOfHour();
        $round = $dominion->round;
        $round->update(['end_date' => $hour->copy()->addHour()]);
        RoundTickRun::create(['round_id' => $round->id, 'tick_at' => $hour]);
        $this->assertEquals([$hour], app(TickService::class)->getDueTickHours($round, $hour->copy()->addHours(3)));
    }

    public function testRecoveryCommandDoesNotBootstrapAnUnregisteredRoundMidHour(): void
    {
        $dominion = $this->prepareDominion();
        $tick = \Mockery::mock(TickService::class)->makePartial();
        $tick->shouldReceive('performTick')->andReturnUsing(function ($round) use ($dominion): bool {
            $this->assertNotSame($dominion->round_id, $round->id);
            return false;
        });
        $tick->recoverHourlyTicks();
        $this->assertSame(0, RoundTickRun::where('round_id', $dominion->round_id)->count());
    }

}

class FaultInjectingTickService extends TickService
{
    public ?string $failAt = null;

    protected function applyTickChanges(array $dominionIds): void
    {
        parent::applyTickChanges($dominionIds);
        $this->failIf('resources');
    }

    protected function performSpellEffects(array $dominionIds): void
    {
        parent::performSpellEffects($dominionIds);
        $this->failIf('spell effects');
    }

    protected function cleanupQueues(Dominion $dominion): void
    {
        parent::cleanupQueues($dominion);
        $this->failIf('cleanup');
    }

    public function precalculateTick(Dominion $dominion, ?bool $saveHistory = false, bool $stateIsFresh = false): void
    {
        parent::precalculateTick($dominion, $saveHistory, $stateIsFresh);
        if ($this->isProcessingTick()) {
            $this->failIf('prediction');
        }
    }

    protected function failIf(string $stage): void
    {
        if ($this->failAt === $stage) {
            throw new RuntimeException('Injected ' . $stage);
        }
    }
}
