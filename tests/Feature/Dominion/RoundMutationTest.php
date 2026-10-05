<?php

namespace OpenDominion\Tests\Feature\Dominion;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Mockery;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Controllers\Dominion\MiscController;
use OpenDominion\Http\Middleware\CoordinateRoundMutation;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\RoundTickRun;
use OpenDominion\Services\Dominion\AIService;
use OpenDominion\Services\Dominion\AutomationService;
use OpenDominion\Services\Dominion\RoundMutationService;
use OpenDominion\Services\Dominion\SelectorService;
use OpenDominion\Services\Dominion\TickService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;
use RuntimeException;

class RoundMutationTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    public function testMutationRefreshesStaleDominionBeforeValidation(): void
    {
        $round = $this->createRound('-2 days');
        $dominion = $this->createDominionWithLegacyStats($this->createUser(), $round);
        Dominion::query()->whereKey($dominion->id)->update(['resource_platinum' => 123]);

        app(RoundMutationService::class)->runForDominion($dominion, function (Dominion $lockedDominion) use ($dominion): void {
            $this->assertSame($dominion, $lockedDominion);
            $this->assertSame(123, $lockedDominion->resource_platinum);
            $this->assertGreaterThan(1, $lockedDominion->getConnection()->transactionLevel());
        });
    }

    public function testMutationFailureRollsBackDominionAndHistory(): void
    {
        $round = $this->createRound('-2 days');
        $dominion = $this->createDominionWithLegacyStats($this->createUser(), $round);
        $platinum = $dominion->resource_platinum;
        $historyCount = $dominion->history()->count();

        try {
            app(RoundMutationService::class)->runForDominion($dominion, function (Dominion $lockedDominion): void {
                $lockedDominion->resource_platinum = 123;
                $lockedDominion->save();
                throw new RuntimeException('Injected mutation failure');
            });
            $this->fail('Expected mutation failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected mutation failure', $exception->getMessage());
        }

        $this->assertSame($platinum, $dominion->fresh()->resource_platinum);
        $this->assertSame($historyCount, $dominion->history()->count());
    }

    public function testPendingHourlyRunBlocksOrdinaryActions(): void
    {
        $round = $this->createRound('-2 days');
        RoundTickRun::query()->create(['round_id' => $round->id, 'tick_at' => now()->startOfHour()]);

        $this->expectException(GameException::class);
        app(RoundMutationService::class)->run($round, function (): void {
            $this->fail('A pending tick must block the action.');
        });
    }

    public function testOverdueCompletedHourBlocksOrdinaryActions(): void
    {
        $round = $this->createRound('-2 days');
        RoundTickRun::query()->create([
            'round_id' => $round->id,
            'tick_at' => now()->startOfHour()->subHour(),
            'completed_at' => now()->subHour(),
        ]);

        $this->expectException(GameException::class);
        app(RoundMutationService::class)->run($round, fn () => null);
    }

    public function testCurrentHourAllowsOrdinaryActions(): void
    {
        $round = $this->createRound('-2 days');
        RoundTickRun::query()->create([
            'round_id' => $round->id,
            'tick_at' => now()->startOfHour(),
            'completed_at' => now(),
        ]);

        $this->assertSame('allowed', app(RoundMutationService::class)->run($round, fn () => 'allowed'));
    }

    public function testProtectionCannotAdvanceWhileActiveRoundIsRecovering(): void
    {
        $round = $this->createRound('-2 days');
        $dominion = $this->createDominionWithLegacyStats($this->createUser(), $round);
        $dominion->update(['protection_finished' => false]);
        RoundTickRun::query()->create(['round_id' => $round->id, 'tick_at' => now()->startOfHour()]);

        $this->expectException(GameException::class);
        app(RoundMutationService::class)->runForDominion($dominion, function (): void {
            $this->fail('Protection must not change eligibility during recovery.');
        });
    }

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

    public function testAiReloadsDisabledStateBeforePerformingActions(): void
    {
        $round = $this->createRound('-2 days');
        $dominion = $this->createDominionWithLegacyStats($this->createUser(), $round);
        $dominion->update(['ai_enabled' => true, 'ai_config' => []]);
        Dominion::query()->whereKey($dominion->id)->update(['ai_enabled' => false]);
        $service = Mockery::mock(AIService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldNotReceive('performLockedActions');

        $service->performActions($dominion);

        $this->assertFalse((bool) $dominion->ai_enabled);
    }
    public function testReadOnlyRequestDoesNotHoldRoundTransaction(): void
    {
        $request = Request::create('/dominion/status', 'GET');
        $request->setRouteResolver(fn () => new Route('GET', 'dominion/status', fn () => null));
        $mutations = Mockery::mock(RoundMutationService::class);
        $mutations->shouldNotReceive('run');
        $mutations->shouldNotReceive('runForDominion');
        $middleware = new CoordinateRoundMutation($mutations, app(SelectorService::class));
        $transactionLevel = (new Dominion())->getConnection()->transactionLevel();

        $response = $middleware->handle($request, function () use ($transactionLevel) {
            $this->assertSame($transactionLevel, (new Dominion())->getConnection()->transactionLevel());
            return response('read only');
        });

        $this->assertSame('read only', $response->getContent());
    }

    public function testMutationRequestRevalidatesSelectedDominionAfterLock(): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('-2 days');
        $dominion = $this->createAndSelectDominionWithLegacyStats($user, $round);
        app(SelectorService::class)->getUserSelectedDominion();
        Dominion::query()->whereKey($dominion->id)->update(['locked_at' => now()]);
        $originalDraftRate = $dominion->draft_rate;

        $this->post(route('dominion.military.change-draft-rate'), ['draft_rate' => 73]);

        $this->assertSame($originalDraftRate, $dominion->fresh()->draft_rate);
        $this->assertTrue(session()->has('errors'));
    }

    public function testProtectionGetRequestUsesRoundCoordination(): void
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
        $this->get($url);

        $this->assertSame(29, $dominion->fresh()->protection_ticks_remaining, json_encode(session()->get('errors')?->all()));

        $this->get($url);

        $this->assertSame(29, $dominion->fresh()->protection_ticks_remaining);
        $this->assertTrue(session()->has('errors'));
    }

    public function testRenderedHttpErrorRollsBackMutation(): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('+2 days');
        $dominion = $this->createAndSelectDominionWithLegacyStats($user, $round);
        $originalPlatinum = $dominion->resource_platinum;
        $request = Request::create('/dominion/bank', 'POST');
        $route = (new Route('POST', 'dominion/bank', fn () => null))->bind($request);
        $request->setRouteResolver(fn () => $route);

        $response = app(CoordinateRoundMutation::class)->handle($request, function () use ($dominion) {
            Dominion::query()->whereKey($dominion->id)->update(['resource_platinum' => 123]);
            return response('Rendered controller failure', 500);
        });

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame($originalPlatinum, $dominion->fresh()->resource_platinum);
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

}
