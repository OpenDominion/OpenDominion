<?php

namespace OpenDominion\Tests\Unit\Services\Dominion;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\AutomationService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class AutomationServiceTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    protected Dominion $dominion;

    protected Round $round;

    protected AutomationService $automationService;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-14 14:00:00');
        $this->round = $this->createRound('-2 days', '+45 days');
        $this->dominion = $this->createDominion(
            $this->createUser(),
            $this->round,
            null,
            null,
            [
                'protection_finished' => true,
                'protection_ticks_remaining' => 0,
                'daily_actions' => AutomationService::DAILY_ACTIONS,
            ]
        );
        $this->automationService = $this->app->make(AutomationService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testItCopiesAnEntireTickToMultipleTargetsAndAppendsToExistingActions(): void
    {
        $currentTick = $this->round->getTick();
        $sourceActions = [
            $this->automationAction('construct', 'alchemy', 45),
            $this->automationAction('train', 'unit2', 120),
        ];
        $existingAction = $this->automationAction('daily_bonus', 'platinum');
        $this->setConfig([
            $currentTick + 1 => $sourceActions,
            $currentTick + 3 => [$existingAction],
        ]);

        $this->automationService->copyTick(
            $this->dominion,
            $currentTick + 1,
            [$currentTick + 2, $currentTick + 3]
        );

        $this->dominion->refresh();
        $this->assertSame($sourceActions, $this->dominion->ai_config[$currentTick + 2]);
        $this->assertSame(
            [$existingAction, ...$sourceActions],
            $this->dominion->ai_config[$currentTick + 3]
        );
    }

    public function testTickCopyIsAtomicWhenATargetWouldExceedTenActions(): void
    {
        $currentTick = $this->round->getTick();
        $sourceActions = [
            $this->automationAction('construct', 'alchemy', 45),
            $this->automationAction('train', 'unit2', 120),
        ];
        $targetActions = array_fill(0, 9, $this->automationAction('daily_bonus', 'land'));
        $originalConfig = [
            $currentTick + 1 => $sourceActions,
            $currentTick + 2 => $targetActions,
        ];
        $this->setConfig($originalConfig);

        try {
            $this->automationService->copyTick(
                $this->dominion,
                $currentTick + 1,
                [$currentTick + 2, $currentTick + 3]
            );
            $this->fail('Expected the oversized target tick to be rejected.');
        } catch (GameException $exception) {
            $this->assertSame('You cannot schedule more than 10 actions in a single hour.', $exception->getMessage());
        }

        $this->dominion->refresh();
        $this->assertSame($originalConfig, $this->dominion->ai_config);
    }

    public function testItCanCopyAStillQueuedCurrentTickToAFutureTick(): void
    {
        $currentTick = $this->round->getTick();
        $actions = [
            $this->automationAction('construct', 'alchemy', 45),
            $this->automationAction('daily_bonus', 'land'),
        ];
        $this->setConfig([$currentTick => $actions]);

        $this->automationService->copyTick($this->dominion, $currentTick, [$currentTick + 2]);

        $this->dominion->refresh();
        $this->assertSame($actions, $this->dominion->ai_config[$currentTick]);
        $this->assertSame($actions, $this->dominion->ai_config[$currentTick + 2]);
    }

    public function testItSavesAndLoadsTemplatesUsingRelativeTickOffsets(): void
    {
        $currentTick = $this->round->getTick();
        $firstActions = [$this->automationAction('construct', 'alchemy', 45)];
        $secondActions = [$this->automationAction('spell', 'midas_touch')];
        $this->setConfig([
            $currentTick + 1 => $firstActions,
            $currentTick + 4 => $secondActions,
        ]);

        $this->automationService->saveTemplate($this->dominion, 0, 'Growth opening');

        $this->dominion->refresh();
        $template = $this->automationService->getTemplates($this->dominion)[0];
        $this->assertSame('Growth opening', $template['name']);
        $this->assertSame([1, 4], array_column($template['ticks'], 'offset'));

        $this->dominion->update(['ai_config' => null, 'ai_enabled' => false]);
        Carbon::setTestNow(Carbon::now()->addDay());
        $newCurrentTick = $this->round->getTick();

        $this->automationService->loadTemplate($this->dominion, 0, 'replace');

        $this->dominion->refresh();
        $this->assertSame($firstActions, $this->dominion->ai_config[$newCurrentTick + 1]);
        $this->assertSame($secondActions, $this->dominion->ai_config[$newCurrentTick + 4]);
        $this->assertTrue($this->dominion->ai_enabled);
    }

    public function testSavingATemplateOmitsTheCurrentTick(): void
    {
        $currentTick = $this->round->getTick();
        $futureActions = [$this->automationAction('construct', 'alchemy', 45)];
        $this->setConfig([
            $currentTick => [$this->automationAction('daily_bonus', 'land')],
            $currentTick + 2 => $futureActions,
        ]);

        $this->automationService->saveTemplate($this->dominion, 0, 'Future actions');

        $this->dominion->refresh();
        $template = $this->automationService->getTemplates($this->dominion)[0];
        $this->assertSame([2], array_column($template['ticks'], 'offset'));
        $this->assertSame($futureActions, $template['ticks'][0]['actions']);
    }

    public function testReplaceLoadingPreservesAPaidCurrentTickForATwoTickTemplate(): void
    {
        $currentTick = $this->round->getTick();
        $firstActions = [$this->automationAction('construct', 'alchemy', 45)];
        $secondActions = [$this->automationAction('train', 'unit2', 120)];
        $this->setConfig([
            $currentTick + 1 => $firstActions,
            $currentTick + 3 => $secondActions,
        ]);
        $this->automationService->saveTemplate($this->dominion, 0, 'Two ticks');

        $currentActions = [$this->automationAction('spell', 'midas_touch')];
        $this->setConfig([
            $currentTick => $currentActions,
            $currentTick + 6 => [$this->automationAction('daily_bonus', 'land')],
        ]);

        $this->automationService->loadTemplate($this->dominion, 0, 'replace');

        $this->dominion->refresh();
        $this->assertSame($currentActions, $this->dominion->ai_config[$currentTick]);
        $this->assertSame($firstActions, $this->dominion->ai_config[$currentTick + 1]);
        $this->assertSame($secondActions, $this->dominion->ai_config[$currentTick + 3]);
        $this->assertArrayNotHasKey($currentTick + 6, $this->dominion->ai_config);
    }

    public function testReplaceLoadingRemovesAPaidCurrentTickForAThreeTickTemplate(): void
    {
        $currentTick = $this->round->getTick();
        $templateActions = [
            1 => [$this->automationAction('construct', 'alchemy', 45)],
            2 => [$this->automationAction('train', 'unit2', 120)],
            3 => [$this->automationAction('spell', 'midas_touch')],
        ];
        $this->setConfig([
            $currentTick + 1 => $templateActions[1],
            $currentTick + 2 => $templateActions[2],
            $currentTick + 3 => $templateActions[3],
        ]);
        $this->automationService->saveTemplate($this->dominion, 0, 'Three ticks');

        $this->setConfig([
            $currentTick => [$this->automationAction('construct', 'homes', 10)],
            $currentTick + 6 => [$this->automationAction('daily_bonus', 'land')],
        ]);

        $this->automationService->loadTemplate($this->dominion, 0, 'replace');

        $this->dominion->refresh();
        $this->assertArrayNotHasKey($currentTick, $this->dominion->ai_config);
        foreach ($templateActions as $offset => $actions) {
            $this->assertSame($actions, $this->dominion->ai_config[$currentTick + $offset]);
        }
        $this->assertArrayNotHasKey($currentTick + 6, $this->dominion->ai_config);
    }

    public function testReplaceLoadingPreservesAFreeCurrentTickForAThreePaidTickTemplate(): void
    {
        $currentTick = $this->round->getTick();
        $this->setConfig([
            $currentTick + 1 => [$this->automationAction('construct', 'alchemy', 45)],
            $currentTick + 2 => [$this->automationAction('train', 'unit2', 120)],
            $currentTick + 3 => [$this->automationAction('spell', 'midas_touch')],
        ]);
        $this->automationService->saveTemplate($this->dominion, 0, 'Three ticks');

        $currentActions = [$this->automationAction('daily_bonus', 'platinum')];
        $this->setConfig([$currentTick => $currentActions]);

        $this->automationService->loadTemplate($this->dominion, 0, 'replace');

        $this->dominion->refresh();
        $this->assertSame($currentActions, $this->dominion->ai_config[$currentTick]);
    }

    public function testOpenTickTemplateLoadingSkipsCollisions(): void
    {
        $currentTick = $this->round->getTick();
        $templateFirstActions = [$this->automationAction('construct', 'alchemy', 45)];
        $templateSecondActions = [$this->automationAction('daily_bonus', 'platinum')];
        $this->setConfig([
            $currentTick + 1 => $templateFirstActions,
            $currentTick + 3 => $templateSecondActions,
        ]);
        $this->automationService->saveTemplate($this->dominion, 1, 'Daily setup');

        $existingActions = [$this->automationAction('train', 'unit2', 80)];
        $this->setConfig([
            $currentTick + 1 => $existingActions,
        ]);
        $this->automationService->loadTemplate($this->dominion, 1, 'open');

        $this->dominion->refresh();
        $this->assertSame($existingActions, $this->dominion->ai_config[$currentTick + 1]);
        $this->assertSame($templateSecondActions, $this->dominion->ai_config[$currentTick + 3]);
    }

    public function testItMovesAnActionDirectlyToARequestedPosition(): void
    {
        $currentTick = $this->round->getTick();
        $actions = [
            $this->automationAction('construct', 'alchemy', 45),
            $this->automationAction('train', 'unit2', 120),
            $this->automationAction('daily_bonus', 'platinum'),
        ];
        $this->setConfig([$currentTick + 1 => $actions]);

        $this->automationService->moveAction($this->dominion, $currentTick + 1, 0, 2);

        $this->dominion->refresh();
        $this->assertSame(
            [$actions[1], $actions[2], $actions[0]],
            $this->dominion->ai_config[$currentTick + 1]
        );
    }

    public function testTickCopyIsRejectedWhenItWouldExceedTheDailyPaidTickLimit(): void
    {
        $currentTick = $this->round->getTick();
        $originalConfig = [
            $currentTick + 1 => [$this->automationAction('construct', 'alchemy', 45)],
        ];
        $this->dominion->daily_actions = 1;
        $this->setConfig($originalConfig);

        try {
            $this->automationService->copyTick($this->dominion, $currentTick + 1, [$currentTick + 2]);
            $this->fail('Expected the daily paid tick limit to reject the copy.');
        } catch (GameException $exception) {
            $this->assertSame('You do not have enough scheduled actions remaining.', $exception->getMessage());
        }

        $this->dominion->refresh();
        $this->assertSame($originalConfig, $this->dominion->ai_config);
    }

    public function testTickCopyRejectsTargetsOutsideTheTwelveHourWindow(): void
    {
        $currentTick = $this->round->getTick();
        $originalConfig = [
            $currentTick + 1 => [$this->automationAction('daily_bonus', 'land')],
        ];
        $this->setConfig($originalConfig);

        try {
            $this->automationService->copyTick(
                $this->dominion,
                $currentTick + 1,
                [$currentTick + AutomationService::MAX_SCHEDULE_HOURS + 1]
            );
            $this->fail('Expected the target outside the scheduling window to be rejected.');
        } catch (GameException $exception) {
            $this->assertSame('You cannot schedule actions more than 12 hours in advance.', $exception->getMessage());
        }

        $this->dominion->refresh();
        $this->assertSame($originalConfig, $this->dominion->ai_config);
    }

    public function testMalformedTickDataUsesAnAccurateValidationError(): void
    {
        $currentTick = $this->round->getTick();
        $this->setConfig([$currentTick + 2 => 'invalid']);

        $this->expectException(GameException::class);
        $this->expectExceptionMessage('Invalid automated action data.');

        $this->automationService->setConfig($this->dominion, [
            'tick' => $currentTick + 1,
            'value' => $this->automationAction('daily_bonus', 'land'),
        ]);
    }

    protected function setConfig(array $config): void
    {
        $this->dominion->ai_config = $config;
        $this->dominion->ai_enabled = !empty($config);
        $this->dominion->save();
    }

    protected function automationAction(string $action, ?string $key = null, ?int $amount = null): array
    {
        return [
            'action' => $action,
            'key' => $key,
            'key2' => null,
            'amount' => $amount,
        ];
    }
}
