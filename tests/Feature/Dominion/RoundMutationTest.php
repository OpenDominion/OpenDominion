<?php

namespace OpenDominion\Tests\Feature\Dominion;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Mockery;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Controllers\Dominion\MiscController;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\RoundTickRun;
use OpenDominion\Services\Dominion\AutomationService;
use OpenDominion\Services\Dominion\SelectorService;
use OpenDominion\Services\Dominion\TickService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;
use RuntimeException;

class RoundMutationTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    public function testManualProtectionAdvanceRollsBackConsumedTickOnFailure(): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('+2 days');
        $dominion = $this->createAndSelectDominionWithLegacyStats($user, $round);
        Dominion::query()->whereKey($dominion->id)->update(['protection_ticks_remaining' => 30, 'protection_finished' => false, 'protection_type' => 'standard', 'last_tick_at' => now()->subHour()]);
        app(SelectorService::class)->forgetSelectedDominion();
        $historyCount = $dominion->history()->count();

        $tick = Mockery::mock(TickService::class);
        $tick->shouldReceive('isProcessingTick')->andReturn(true);
        $tick->shouldReceive('performTick')->once()->andThrow(new RuntimeException('Injected tick failure'));
        $this->app->instance(TickService::class, $tick);

        try {
            app(MiscController::class)->getTickDominion(Request::create('/dominion/misc/tick'));
            $this->fail('Expected tick failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected tick failure', $exception->getMessage());
        }

        $this->assertSame(30, $dominion->fresh()->protection_ticks_remaining);
        $this->assertSame($historyCount, $dominion->history()->count());
    }

    public function testProtectionRefreshesAndLocksOnlyItsOwnDominion(): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('+2 days');
        $dominion = $this->createAndSelectDominionWithLegacyStats($user, $round);
        Dominion::query()->whereKey($dominion->id)->update([
            'protection_finished' => false,
            'protection_ticks_remaining' => 30,
            'last_tick_at' => now()->subHour(),
        ]);
        $tick = Mockery::mock(TickService::class);
        $tick->shouldReceive('isProcessingTick')->andReturn(true);
        $tick->shouldReceive('performTick')->once()->andReturnUsing(function () use ($dominion): bool {
            $this->assertGreaterThan(1, $dominion->getConnection()->transactionLevel());
            return true;
        });
        $this->app->instance(TickService::class, $tick);

        $url = route('dominion.misc.tick', ['expected_protection_ticks_remaining' => 30]);
        $connection = $dominion->getConnection();
        $connection->flushQueryLog();
        $connection->enableQueryLog();
        try {
            $this->get($url);
            $queries = collect($connection->getQueryLog())->pluck('query');
        } finally {
            $connection->disableQueryLog();
        }
        $this->assertCount(0, $queries->filter(fn ($sql) => str_contains($sql, '`rounds`') && (str_contains($sql, 'lock in share mode') || str_contains($sql, 'for update'))));
        $this->assertCount(1, $queries->filter(fn ($sql) => str_contains($sql, '`dominions`') && str_contains($sql, 'for update')));

        $this->assertSame(29, $dominion->fresh()->protection_ticks_remaining, json_encode(session()->get('errors')?->all()));

        $this->get($url);

        $this->assertSame(29, $dominion->fresh()->protection_ticks_remaining);
        $this->assertTrue(session()->has('errors'));
    }

    public function testProtectionImportKeepsCompletedHoursAndRollsBackFailedHour(): void
    {
        $round = $this->createRound('+2 days');
        $dominion = $this->createDominionWithLegacyStats($this->createUser(), $round);
        $dominion->update(['protection_ticks_remaining' => 72, 'protection_finished' => false]);
        $tick = Mockery::mock(TickService::class);
        $tick->shouldReceive('isProcessingTick')->andReturn(true);
        $tick->shouldReceive('performTick')->once()->andReturn(true);
        $this->app->instance(TickService::class, $tick);

        try {
            app(AutomationService::class)->processLog($dominion, [
                1 => [['type' => 'draftrate', 'data' => 40, 'line' => 1]],
                2 => [
                    ['type' => 'draftrate', 'data' => 50, 'line' => 2],
                    ['type' => 'construction', 'data' => ['building_home' => 1000000], 'line' => 3],
                ],
            ]);
            $this->fail('Expected invalid construction to abort the second hour.');
        } catch (GameException $exception) {
            $this->assertStringContainsString('Error processing hour 2 line 3', $exception->getMessage());
        }

        $this->assertSame(71, $dominion->fresh()->protection_ticks_remaining);
        $this->assertSame(40, $dominion->fresh()->draft_rate);
        $this->assertSame(71, $dominion->protection_ticks_remaining);
    }

    public function testActivityTrackingPreservesPredictionDuringPendingTick(): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('-2 days');
        $dominion = $this->createAndSelectDominionWithLegacyStats($user, $round);
        Dominion::query()->whereKey($dominion->id)->update(['hourly_activity' => null]);
        $dominion->tick->update(['resource_platinum' => 123]);
        $prediction = $dominion->tick->fresh()->getAttributes();
        RoundTickRun::query()->create(['round_id' => $round->id, 'tick_at' => now()->startOfHour()]);
        app(SelectorService::class)->forgetSelectedDominion();

        $selectedDominion = app(SelectorService::class)->getUserSelectedDominion();

        $this->assertSame('1', $selectedDominion->hourly_activity[(int) $round->getTick()]);
        $this->assertSame($selectedDominion->hourly_activity, $dominion->fresh()->hourly_activity);
        $this->assertSame($prediction, $dominion->tick->fresh()->getAttributes());
    }

    public function testPendingTickAllowsJournalAndNotificationRequests(): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('-2 days');
        $dominion = $this->createAndSelectDominionWithLegacyStats($user, $round);
        $prediction = $dominion->tick->getAttributes();
        $notification = $dominion->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'test',
            'data' => ['message' => 'test'],
        ]);
        RoundTickRun::query()->create(['round_id' => $round->id, 'tick_at' => now()->startOfHour()]);

        $this->post(route('dominion.journal.create'), ['content' => 'Waiting for the next hour.']);

        $this->assertFalse(session()->has('errors'));
        $this->assertSame('Waiting for the next hour.', $dominion->journals()->sole()->content);

        $this->post(route('dominion.misc.clear-notifications'));

        $this->assertFalse(session()->has('errors'));
        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertSame($prediction, $dominion->tick->fresh()->getAttributes());
    }

    public function testPendingTickAllowsGameAndCommunicationRoutes(): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('-2 days');
        $dominion = $this->createAndSelectDominionWithLegacyStats($user, $round);
        RoundTickRun::query()->create(['round_id' => $round->id, 'tick_at' => now()->startOfHour()]);

        foreach ([
            [route('dominion.military.change-draft-rate'), ['draft_rate' => 73]],
            [route('dominion.forum.create'), ['title' => 'Test', 'body' => 'Test']],
            [route('dominion.council.create'), ['title' => 'Test', 'body' => 'Test']],
        ] as [$url, $parameters]) {
            session()->forget('errors');
            $this->post($url, $parameters);
            $this->assertFalse(session()->has('errors'), json_encode(session('errors')?->all()));
        }

        $this->assertSame(73, $dominion->fresh()->draft_rate);
        $this->assertSame(1, $round->forumThreads()->count());
        $this->assertSame(1, $dominion->realm->councilThreads()->count());
    }

    public function testForumFlagGetCanRunDuringPendingTick(): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('-2 days');
        $dominion = $this->createAndSelectDominionWithLegacyStats($user, $round);
        $thread = app(\OpenDominion\Services\ForumService::class)->createThread($dominion, 'Discussion', 'A forum post.');
        $post = app(\OpenDominion\Services\ForumService::class)->postReply($dominion, $thread, 'A reply.');
        RoundTickRun::query()->create(['round_id' => $round->id, 'tick_at' => now()->startOfHour()]);

        foreach ([
            route('dominion.forum.flag.thread', $thread),
            route('dominion.forum.flag.post', $post),
        ] as $url) {
            session()->forget('errors');
            $this->get($url);
            $this->assertResponseStatus(302);
            $this->assertFalse(session()->has('errors'));
        }

        $this->assertNotNull($thread->fresh()->flagged_by);
        $this->assertNotNull($post->fresh()->flagged_by);
    }

    public function testOverdueCheckpointDoesNotAddActorOrRoundLocksToOrdinaryActions(): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('-2 days');
        $dominion = $this->createAndSelectDominionWithLegacyStats($user, $round);
        Dominion::query()->whereKey($dominion->id)->update(['hourly_activity' => str_repeat('1', 47 * 24)]);
        app(SelectorService::class)->forgetSelectedDominion();
        RoundTickRun::query()->create([
            'round_id' => $round->id,
            'tick_at' => now()->startOfHour()->subHours(2),
            'completed_at' => now()->subHours(2),
        ]);
        $connection = $dominion->getConnection();
        $connection->flushQueryLog();
        $connection->enableQueryLog();
        try {
            $this->post(route('dominion.military.change-draft-rate'), ['draft_rate' => 73]);
            $queries = collect($connection->getQueryLog())->pluck('query');
        } finally {
            $connection->disableQueryLog();
        }

        $this->assertFalse(session()->has('errors'));
        $this->assertSame(73, $dominion->fresh()->draft_rate);
        $this->assertCount(0, $queries->filter(fn ($sql) => str_contains($sql, 'lock in share mode') || str_contains($sql, 'for update')));
    }

    public function testActivityTrackingUsesOnlyANonblockingDominionLock(): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('-2 days');
        $dominion = $this->createAndSelectDominionWithLegacyStats($user, $round);
        Dominion::query()->whereKey($dominion->id)->update(['hourly_activity' => null]);
        app(SelectorService::class)->forgetSelectedDominion();
        $connection = $dominion->getConnection();
        $connection->flushQueryLog();
        $connection->enableQueryLog();
        try {
            app(SelectorService::class)->getUserSelectedDominion();
            $queries = collect($connection->getQueryLog())->pluck('query');
        } finally {
            $connection->disableQueryLog();
        }

        $lockingQueries = $queries->filter(fn ($sql) => str_contains($sql, 'lock in share mode') || str_contains($sql, 'for update'));
        $this->assertCount(1, $lockingQueries);
        $this->assertStringContainsString('`dominions`', $lockingQueries->first());
        $this->assertStringContainsString('for update skip locked', $lockingQueries->first());
    }

    public function testProtectionCanAdvanceWithAnIncompleteCheckpoint(): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('-2 days');
        $dominion = $this->createAndSelectDominionWithLegacyStats($user, $round);
        Dominion::query()->whereKey($dominion->id)->update([
            'protection_ticks_remaining' => 30,
            'protection_finished' => false,
            'protection_type' => 'standard',
            'last_tick_at' => now()->subHour(),
        ]);
        RoundTickRun::query()->create(['round_id' => $round->id, 'tick_at' => now()->startOfHour()]);
        $tick = Mockery::mock(TickService::class);
        $tick->shouldReceive('isProcessingTick')->andReturn(true);
        $tick->shouldReceive('performTick')->once()->andReturn(true);
        $this->app->instance(TickService::class, $tick);

        $this->get(route('dominion.misc.tick'));

        $this->assertResponseStatus(302);
        $this->assertFalse(session()->has('errors'), json_encode(session('errors')?->all()));
        $this->assertSame(29, $dominion->fresh()->protection_ticks_remaining);
    }

    public function testProtectionUndoRollsBackEarlierStepsWhenALaterStepFails(): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('+2 days');
        $dominion = $this->createAndSelectDominionWithLegacyStats($user, $round);
        Dominion::query()->whereKey($dominion->id)->update([
            'protection_ticks_remaining' => 24,
            'protection_finished' => false,
            'protection_type' => 'quick',
            'last_tick_at' => now()->subHour(),
        ]);
        app(SelectorService::class)->forgetSelectedDominion();
        $historyCount = $dominion->history()->count();
        $tick = Mockery::mock(TickService::class);
        $tick->shouldReceive('isProcessingTick')->andReturn(true);
        $tick->shouldReceive('revertTick')->once()->ordered()->andReturnUsing(function (Dominion $lockedDominion): bool {
            $lockedDominion->protection_ticks_remaining += 1;
            $lockedDominion->save();
            return true;
        });
        $tick->shouldReceive('revertTick')->once()->ordered()->andThrow(new RuntimeException('Injected undo failure'));
        $this->app->instance(TickService::class, $tick);

        try {
            app(MiscController::class)->getUndoTickDominion(Request::create('/dominion/misc/undo-tick'));
            $this->fail('Expected undo failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected undo failure', $exception->getMessage());
        }

        $this->assertSame(24, $dominion->fresh()->protection_ticks_remaining);
        $this->assertSame($historyCount, $dominion->history()->count());
    }
    public function testCurrentCheckpointAllowsAnOrdinaryAction(): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('-2 days');
        $dominion = $this->createAndSelectDominionWithLegacyStats($user, $round);
        RoundTickRun::query()->create([
            'round_id' => $round->id,
            'tick_at' => now()->startOfHour(),
            'completed_at' => now(),
        ]);

        $this->post(route('dominion.military.change-draft-rate'), ['draft_rate' => 73]);

        $this->assertFalse(session()->has('errors'));
        $this->assertSame(73, $dominion->fresh()->draft_rate);
    }

    public function testPendingCheckpointPreservesNormalActionValidation(): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('-2 days');
        $dominion = $this->createAndSelectDominionWithLegacyStats($user, $round);
        $draftRate = $dominion->draft_rate;
        RoundTickRun::query()->create(['round_id' => $round->id, 'tick_at' => now()->startOfHour()]);

        $this->post(route('dominion.military.change-draft-rate'), ['draft_rate' => 99]);

        $this->assertTrue(session()->has('errors'));
        $this->assertStringNotContainsString('collecting taxes', session('errors')->first());
        $this->assertSame($draftRate, $dominion->fresh()->draft_rate);
    }

    public function testProtectionRefreshesLockedStateBeforeValidation(): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('+2 days');
        $dominion = $this->createAndSelectDominionWithLegacyStats($user, $round);
        Dominion::query()->whereKey($dominion->id)->update([
            'protection_ticks_remaining' => 30,
            'protection_finished' => false,
            'last_tick_at' => now()->subHour(),
        ]);
        app(SelectorService::class)->getUserSelectedDominion();
        Dominion::query()->whereKey($dominion->id)->update(['locked_at' => now()]);

        $this->get(route('dominion.misc.tick'));

        $this->assertTrue(session()->has('errors'));
        $this->assertStringContainsString('locked', session('errors')->first());
        $this->assertSame(30, $dominion->fresh()->protection_ticks_remaining);
    }

    public function testProtectionLoadsFreshGameRelationsForTheTick(): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('+2 days');
        $dominion = $this->createAndSelectDominionWithLegacyStats($user, $round);
        app(SelectorService::class)->getUserSelectedDominion();
        Dominion::query()->whereKey($dominion->id)->update([
            'protection_ticks_remaining' => 30,
            'protection_finished' => false,
            'protection_type' => 'standard',
            'resource_platinum' => 123,
            'last_tick_at' => now()->subHour(),
        ]);
        $tick = Mockery::mock(TickService::class);
        $tick->shouldReceive('isProcessingTick')->andReturn(true);
        $tick->shouldReceive('performTick')->once()->andReturnUsing(function ($round, Dominion $lockedDominion): bool {
            $this->assertSame(123, $lockedDominion->resource_platinum);
            $this->assertSame(29, $lockedDominion->protection_ticks_remaining);
            foreach (['round', 'race', 'realm', 'hero', 'queues', 'spells', 'techs'] as $relation) {
                $this->assertTrue($lockedDominion->relationLoaded($relation), $relation);
            }
            return true;
        });
        $this->app->instance(TickService::class, $tick);

        $this->get(route('dominion.misc.tick'));

        $this->assertFalse(session()->has('errors'));
    }

    public function testProtectionUndoCanRunWithAnIncompleteCheckpoint(): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('-2 days');
        $dominion = $this->createAndSelectDominionWithLegacyStats($user, $round);
        Dominion::query()->whereKey($dominion->id)->update([
            'protection_ticks_remaining' => 30,
            'protection_finished' => false,
            'protection_type' => 'standard',
            'last_tick_at' => now()->subHour(),
        ]);
        RoundTickRun::query()->create(['round_id' => $round->id, 'tick_at' => now()->startOfHour()]);
        $tick = Mockery::mock(TickService::class);
        $tick->shouldReceive('isProcessingTick')->andReturn(true);
        $tick->shouldReceive('revertTick')->once()->andReturnUsing(function (Dominion $lockedDominion): bool {
            $lockedDominion->protection_ticks_remaining += 1;
            $lockedDominion->save();
            return true;
        });
        $this->app->instance(TickService::class, $tick);

        $this->get(route('dominion.misc.undo-tick'));

        $this->assertFalse(session()->has('errors'));
        $this->assertSame(31, $dominion->fresh()->protection_ticks_remaining);
    }

    public function testUnexpectedProtectionImportFailureRollsBackAllChanges(): void
    {
        $round = $this->createRound('+2 days');
        $dominion = $this->createDominionWithLegacyStats($this->createUser(), $round);
        $dominion->update(['protection_ticks_remaining' => 72, 'protection_finished' => false]);
        $draftRate = $dominion->draft_rate;
        $historyCount = $dominion->history()->count();
        $tick = Mockery::mock(TickService::class);
        $tick->shouldReceive('isProcessingTick')->andReturn(true);
        $tick->shouldReceive('performTick')->once()->andThrow(new RuntimeException('Injected import failure'));
        $this->app->instance(TickService::class, $tick);

        try {
            app(AutomationService::class)->processLog($dominion, [
                1 => [['type' => 'draftrate', 'data' => 40, 'line' => 1]],
            ]);
            $this->fail('Expected import failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected import failure', $exception->getMessage());
        }

        $this->assertSame(72, $dominion->fresh()->protection_ticks_remaining);
        $this->assertSame($draftRate, $dominion->fresh()->draft_rate);
        $this->assertSame($historyCount, $dominion->history()->count());
    }

    public function testActivityTrackingDoesNotSaveUnrelatedStaleAttributes(): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('-2 days');
        $dominion = $this->createAndSelectDominionWithLegacyStats($user, $round);
        $selector = app(SelectorService::class);
        $selectedDominion = $selector->getUserSelectedDominion();
        $selectedDominion->hourly_activity = null;
        $selectedDominion->resource_platinum = 123;
        Dominion::query()->whereKey($dominion->id)->update(['hourly_activity' => null, 'resource_platinum' => 456]);

        $this->assertSame($selectedDominion, $selector->getUserSelectedDominion());

        $this->assertSame(123, $selectedDominion->resource_platinum);
        $this->assertTrue($selectedDominion->isDirty('resource_platinum'));
        $this->assertSame(456, $dominion->fresh()->resource_platinum);
        $this->assertSame('1', $dominion->fresh()->hourly_activity[(int) $round->getTick()]);
    }

    public function testRepeatedReadDoesNotLockAnAlreadyRecordedHour(): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('-2 days');
        $dominion = $this->createAndSelectDominionWithLegacyStats($user, $round);
        app(SelectorService::class)->getUserSelectedDominion();
        $connection = $dominion->getConnection();
        $connection->flushQueryLog();
        $connection->enableQueryLog();
        try {
            app(SelectorService::class)->getUserSelectedDominion();
            $queries = collect($connection->getQueryLog())->pluck('query');
        } finally {
            $connection->disableQueryLog();
        }

        $this->assertCount(0, $queries->filter(fn ($sql) => str_contains($sql, 'for update') || str_starts_with($sql, 'update')));
    }
}
