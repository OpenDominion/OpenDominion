<?php

namespace OpenDominion\Tests\Feature\Magic\WizardGuilds;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\TickService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

/**
 * Wizards trained by Wizard Guilds arrive immediately during the tick rather
 * than going through the training queue.
 */
class WizardGuildTickTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    public function testWizardGuildsTrainWizardsDuringTheTick(): void
    {
        $user = $this->createUser();
        $round = $this->createRound('-7 days');
        $dominion = $this->createDominionWithLegacyStats($user, $round);
        $tickService = app(TickService::class);
        $queueService = app(QueueService::class);

        $dominion->protection_ticks_remaining = 0;
        $dominion->building_wizard_guild = 32;
        $dominion->military_wizards = 100;
        $dominion->save();

        $tickService->performTick($round);

        $this->seeInDatabase('dominions', [
            'id' => $dominion->id,
            'military_wizards' => 102,
        ]);

        $this->assertSame(
            0,
            $queueService->getTrainingQueueTotalByResource($dominion->fresh(), 'military_wizards'),
            'Wizards from Wizard Guilds should not be queued'
        );
    }

    public function testWizardGuildsBelowSixteenTrainNothing(): void
    {
        $user = $this->createUser();
        $round = $this->createRound('-7 days');
        $dominion = $this->createDominionWithLegacyStats($user, $round);
        $tickService = app(TickService::class);

        $dominion->protection_ticks_remaining = 0;
        $dominion->building_wizard_guild = 15;
        $dominion->military_wizards = 100;
        $dominion->save();

        $tickService->performTick($round);

        $this->seeInDatabase('dominions', [
            'id' => $dominion->id,
            'military_wizards' => 100,
        ]);
    }
}
