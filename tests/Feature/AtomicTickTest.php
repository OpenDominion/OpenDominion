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
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\NotificationOutbox;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundTickRun;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Services\Dominion\HeroBattleService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\TickService;
use OpenDominion\Services\NotificationService;
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
        Notification::swap(new ChannelManager($this->app));
        config(['mail.default' => 'array']);
    }

    protected function prepareDominion(): Dominion
    {
        $dominion = $this->createDominionWithLegacyStats(
            $this->createUser(),
            $this->createRound('-7 days'),
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
        $snapshot['notifications'] = $dominion->notifications()->orderBy('id')->get()->toJson();
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
        $dominion->user->update(['settings' => [
            'notifications' => ['hourly_dominion' => ['training_completed' => ['ingame' => true, 'email' => true]]],
        ]]);
        app(QueueService::class)->queueResources('training', $dominion, ['military_unit3' => 7], 1);
        $tick->precalculateTick($dominion);
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
        $this->assertSame(1, $dominion->notifications()->count());
        $this->assertSame(1, DB::table('notification_outbox')->where('dominion_id', $dominion->id)->count());
        $after = $this->snapshot($dominion);
        $this->assertFalse($tick->performTick($dominion->round, null, $hour));
        $this->assertSame($after, $this->snapshot($dominion));
        $this->assertNotNull($run->fresh()->completed_at);
    }

    public function testProtectionNotificationsRollBackWithFailureAndDisappearOnUndo(): void
    {
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

    public function testMissingPredictionIsRebuiltBeforeQueuesAndProductionAdvance(): void
    {
        $dominion = $this->prepareDominion();
        app(QueueService::class)->queueResources('training', $dominion, ['military_unit3' => 7], 1);
        $home = $dominion->military_unit3;
        DB::table('dominion_tick')->where('dominion_id', $dominion->id)->delete();

        $this->assertTrue(app(TickService::class)->performTick($dominion->round));

        $this->assertSame($home + 7, $dominion->fresh()->military_unit3);
        $this->assertSame(1, $dominion->tick()->count());
        $this->assertFalse($dominion->queues()->where('hours', '<=', 0)->exists());
        $this->assertSame(3, (int) $dominion->queues()->where('resource', 'military_unit3')->where('hours', 12)->value('amount'));
        $this->assertSame(7, $dominion->history()->where('event', 'tick')->sole()->delta['military_unit3']);
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

    public function testFailedEarlierHourDoesNotBlockTheCurrentHour(): void
    {
        $dominion = $this->prepareDominion();
        $hour = now()->startOfHour();
        $run = RoundTickRun::create([
            'round_id' => $dominion->round_id, 'tick_at' => $hour->copy()->subHours(3),
            'attempts' => 4, 'last_error' => 'Previous failure',
        ]);
        $this->assertTrue(app(TickService::class)->performTick($dominion->round, null, $hour));

        $run->refresh();
        $this->assertTrue($run->tick_at->equalTo($hour));
        $this->assertNotNull($run->completed_at);
        $this->assertSame(1, $run->attempts);
        $this->assertNull($run->last_error);
        $this->assertSame(1, $dominion->history()->where('event', 'tick')->count());
        $this->assertSame(1, RoundTickRun::where('round_id', $dominion->round_id)->count());
    }

    public function testCompletedLaterHourMakesAnOlderRequestANoop(): void
    {
        $dominion = $this->prepareDominion();
        $tick = app(TickService::class);
        $hour = now()->startOfHour();
        $tick->performTick($dominion->round, null, $hour);
        $before = $this->snapshot($dominion);
        $this->assertFalse($tick->performTick($dominion->round, null, $hour->copy()->subHour()));
        $this->assertFalse($tick->performRoundMaintenance($dominion->round, $hour->copy()->subHour()));
        $this->assertSame($before, $this->snapshot($dominion));
        $this->assertSame(1, RoundTickRun::where('round_id', $dominion->round_id)->count());
    }

    public function testHourlyProcessingDoesNotReplayMissedHours(): void
    {
        $dominion = $this->prepareDominion();
        $hour = now()->startOfHour();
        $previous = $hour->copy()->subHours(3);
        RoundTickRun::create([
            'round_id' => $dominion->round_id, 'tick_at' => $previous,
            'completed_at' => $previous, 'maintenance_completed_at' => $previous,
        ]);
        $tick = new ControlledHourlyTickService();
        $tick->roundIds = [$dominion->round_id];
        $tick->tickHourly();

        $this->assertSame(1, $dominion->history()->where('event', 'tick')->count());
        $run = RoundTickRun::where('round_id', $dominion->round_id)->sole();
        $this->assertTrue($run->tick_at->equalTo($hour));
        $this->assertNotNull($run->maintenance_completed_at);
    }

    public function testFailedRoundDoesNotBlockHealthyRoundProcessing(): void
    {
        $broken = $this->prepareDominion();
        $healthy = $this->prepareDominion();
        $tick = new ControlledHourlyTickService();
        $tick->roundIds = [$broken->round_id, $healthy->round_id];
        $tick->failedRoundId = $broken->round_id;

        try {
            $tick->tickHourly();
            $this->fail('Expected the failed round to be reported after processing healthy rounds.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Hourly processing failed: production for round ' . $broken->round_id, $exception->getMessage());
        }

        $this->assertFalse(RoundTickRun::where('round_id', $broken->round_id)->exists());
        $this->assertNotNull(RoundTickRun::where('round_id', $healthy->round_id)->sole()->maintenance_completed_at);
        $this->assertSame(3, (int) $healthy->queues()->where('resource', 'military_unit3')->where('hours', 12)->value('amount'));
    }

    public function testHourlyProcessingLeavesEndedRoundsUnchanged(): void
    {
        $dominion = $this->prepareDominion();
        $hour = now()->startOfHour();
        $dominion->round->update(['end_date' => $hour->copy()->subHour()]);
        $run = RoundTickRun::create(['round_id' => $dominion->round_id, 'tick_at' => $hour->copy()->subHours(2)]);
        $before = $this->snapshot($dominion);
        $tick = new ControlledHourlyTickService();
        $tick->roundIds = [$dominion->round_id];
        $tick->tickHourly();

        $this->assertSame($before, $this->snapshot($dominion));
        $this->assertNull($run->fresh()->completed_at);
        $this->assertTrue($run->fresh()->tick_at->equalTo($hour->copy()->subHours(2)));
    }

    public function testHourlyProcessingInitializesOneCheckpointAndDoesNotRepeatTheHour(): void
    {
        $dominion = $this->prepareDominion();
        $tick = new ControlledHourlyTickService();
        $tick->roundIds = [$dominion->round_id];
        $tick->tickHourly();
        $after = $this->snapshot($dominion);
        $tick->tickHourly();

        $this->assertSame($after, $this->snapshot($dominion));
        $this->assertSame(1, RoundTickRun::where('round_id', $dominion->round_id)->count());
        $this->assertNotNull(RoundTickRun::where('round_id', $dominion->round_id)->sole()->maintenance_completed_at);
    }

    public function testMaintenanceFailureKeepsProductionAndRetriesMaintenanceOnce(): void
    {
        $dominion = $this->prepareDominion();
        $hour = now()->startOfHour();
        $abandoned = $this->createDominion($this->createUser(), $dominion->round);
        $abandoned->update(['abandoned_at' => $hour, 'api_key' => 'to-be-revoked']);
        $tick = new ControlledHourlyTickService();
        $tick->roundIds = [$dominion->round_id];
        $tick->failAt = 'maintenance';

        try {
            $tick->tickHourly();
            $this->fail('Expected maintenance failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Hourly processing failed: maintenance for round ' . $dominion->round_id, $exception->getMessage());
        }

        $run = RoundTickRun::where('round_id', $dominion->round_id)->sole();
        $this->assertNotNull($run->completed_at);
        $this->assertNull($run->maintenance_completed_at);
        $this->assertSame('Maintenance: Injected maintenance', $run->last_error);
        $this->assertSame('to-be-revoked', $abandoned->fresh()->api_key);
        $events = GameEvent::where('source_id', $abandoned->id)->where('source_type', Dominion::class)->where('type', 'abandoned');
        $this->assertSame(0, (clone $events)->count());
        $afterProduction = $this->snapshot($dominion);

        $tick->failAt = null;
        $tick->tickHourly();
        $this->assertSame($afterProduction, $this->snapshot($dominion));
        $this->assertNotNull($run->fresh()->maintenance_completed_at);
        $this->assertNull($run->fresh()->last_error);
        $this->assertNull($abandoned->fresh()->api_key);
        $this->assertSame(1, (clone $events)->count());
        $tick->tickHourly();
        $this->assertSame(1, (clone $events)->count());
        $this->assertSame(1, $run->fresh()->attempts);
    }

    public function testConstructionBatchesCompleteOnceAfterAFailedHourWithoutStaleNotifications(): void
    {
        $dominion = $this->prepareDominion();
        $dominion->update(['land_plain' => $dominion->land_plain + 100]);
        $dominion->user->update(['settings' => [
            'notifications' => ['hourly_dominion' => ['construction_completed' => ['ingame' => true, 'email' => true]]],
        ]]);
        $queue = app(QueueService::class);
        $queue->queueResources('construction', $dominion, ['building_home' => 7], 1);
        $queue->queueResources('construction', $dominion, ['building_home' => 11], 2);
        $homes = $dominion->building_home;
        $tick = new FaultInjectingTickService();
        $this->app->instance(TickService::class, $tick);
        $tick->precalculateTick($dominion);
        $before = $this->snapshot($dominion);
        $hour = now()->startOfHour();
        $tick->failAt = 'prediction';
        try {
            $tick->performTick($dominion->round, null, $hour);
            $this->fail('Expected failure after queue cleanup and notification persistence.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected prediction', $exception->getMessage());
        }
        $this->assertSame($before, $this->snapshot($dominion));

        $tick->failAt = null;
        $this->assertTrue($tick->performTick($dominion->round, null, $hour->copy()->addHour()));
        $this->assertSame($homes + 7, $dominion->fresh()->building_home);
        $this->assertSame(11, $dominion->fresh()->tick->building_home);
        $this->assertSame(11, (int) $dominion->queues()->where('source', 'construction')->where('hours', 1)->value('amount'));
        $this->assertGreaterThanOrEqual(0, app(LandCalculator::class)->getTotalBarrenLand($dominion->fresh()));
        $this->assertTrue($tick->performTick($dominion->round, null, $hour->copy()->addHours(2)));
        $this->assertSame($homes + 18, $dominion->fresh()->building_home);
        $this->assertFalse($dominion->queues()->where('source', 'construction')->exists());
        $this->assertGreaterThanOrEqual(0, app(LandCalculator::class)->getTotalBarrenLand($dominion->fresh()));
        $notifications = $dominion->notifications()->reorder('created_at')->get()
            ->filter(fn ($notification) => $notification->data['type'] === 'construction_completed')->values();
        $this->assertSame([7, 11], $notifications->map(fn ($notification) => $notification->data['data']['building_home'])->all());
        $outbox = NotificationOutbox::where('dominion_id', $dominion->id)->orderBy('event_at')->get();
        $this->assertSame([7, 11], $outbox->map(fn ($notification) => $notification->payload['construction_completed']['building_home'])->all());
        $this->assertCount(2, $outbox->pluck('operation_key')->unique());
        $this->assertSame(1, RoundTickRun::where('round_id', $dominion->round_id)->count());
    }

    public function testMaintenanceNotificationsRollBackAndRetryOnce(): void
    {
        $dominion = $this->prepareDominion();
        $dominion->user->update(['settings' => [
            'notifications' => ['hourly_dominion' => ['training_completed' => ['ingame' => true, 'email' => true]]],
        ]]);
        $battles = \Mockery::mock(HeroBattleService::class);
        $battles->shouldReceive('processBattles')->twice()->andReturnUsing(function (Round $round) use ($dominion): void {
            $notifications = app(NotificationService::class);
            $notifications->queueNotification('training_completed', ['military_unit3' => 5]);
            $notifications->sendNotifications($dominion->fresh(), 'hourly_dominion');
        });
        $this->app->instance(HeroBattleService::class, $battles);
        $tick = new FaultInjectingTickService();
        $hour = now()->startOfHour();
        $tick->performTick($dominion->round, null, $hour);
        $tick->failAt = 'maintenance';
        try {
            $tick->performRoundMaintenance($dominion->round, $hour);
            $this->fail('Expected maintenance failure after notification persistence.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected maintenance', $exception->getMessage());
        }
        $this->assertSame(0, $dominion->notifications()->count());
        $this->assertSame(0, NotificationOutbox::where('dominion_id', $dominion->id)->count());
        $this->assertNotNull(RoundTickRun::where('round_id', $dominion->round_id)->sole()->completed_at);

        $tick->failAt = null;
        $this->assertTrue($tick->performRoundMaintenance($dominion->round, $hour));
        $this->assertFalse($tick->performRoundMaintenance($dominion->round, $hour));
        $this->assertSame(1, $dominion->notifications()->count());
        $this->assertSame(1, NotificationOutbox::where('dominion_id', $dominion->id)->count());
        $this->assertSame(5, $dominion->notifications()->sole()->data['data']['military_unit3']);
    }

    public function testMaintenanceRequiresCompletedProduction(): void
    {
        $dominion = $this->prepareDominion();
        $hour = now()->startOfHour();
        RoundTickRun::create(['round_id' => $dominion->round_id, 'tick_at' => $hour]);
        $this->assertFalse(app(TickService::class)->performRoundMaintenance($dominion->round, $hour));
        $this->assertNull(RoundTickRun::where('round_id', $dominion->round_id)->sole()->maintenance_completed_at);
    }

    public function testWorkerSupersededAfterRegistrationCannotApplyAnOlderHour(): void
    {
        $dominion = $this->prepareDominion();
        $hour = now()->startOfHour();
        $tick = new SupersededTickService();
        $tick->newerHour = $hour->copy()->addHour();
        $this->assertFalse($tick->performTick($dominion->round, null, $hour));

        $this->assertSame(1, $dominion->history()->where('event', 'tick')->count());
        $this->assertTrue($dominion->fresh()->last_tick_at->equalTo($tick->newerHour));
        $this->assertTrue(RoundTickRun::where('round_id', $dominion->round_id)->sole()->tick_at->equalTo($tick->newerHour));
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

    protected function performDailyRoundTasks(Round $round): void
    {
        parent::performDailyRoundTasks($round);
        $this->failIf('maintenance');
    }

    protected function failIf(string $stage): void
    {
        if ($this->failAt === $stage) {
            throw new RuntimeException('Injected ' . $stage);
        }
    }
}

class ControlledHourlyTickService extends FaultInjectingTickService
{
    public array $roundIds = [];

    public ?int $failedRoundId = null;

    public function performTick(Round $round, ?Dominion $dominion = null, ?Carbon $tickAt = null): bool
    {
        if (!in_array($round->id, $this->roundIds, true)) {
            return false;
        }
        if ($round->id === $this->failedRoundId) {
            throw new RuntimeException('Injected round failure');
        }

        return parent::performTick($round, $dominion, $tickAt);
    }

    public function performRoundMaintenance(Round $round, Carbon $tickAt): bool
    {
        return in_array($round->id, $this->roundIds, true) && parent::performRoundMaintenance($round, $tickAt);
    }

    protected function performRoundSetup(Round $round, string $operation, callable $callback): void
    {
    }
}

class SupersededTickService extends TickService
{
    public Carbon $newerHour;

    protected function registerRoundTick(Round $round, Carbon $tickAt): ?RoundTickRun
    {
        $run = parent::registerRoundTick($round, $tickAt);
        (new TickService())->performTick($round, null, $this->newerHour);

        return $run;
    }
}
