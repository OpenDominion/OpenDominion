<?php

namespace OpenDominion\Tests\Feature\Dominion;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Models\Dominion;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class AutomationTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    protected Dominion $dominion;

    protected function setUp(): void
    {
        parent::setUp();

        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('-2 days', '+45 days');
        $this->dominion = $this->createAndSelectDominionWithLegacyStats($user, $round);

        $currentTick = $round->getTick();
        $this->dominion->update([
            'protection_finished' => true,
            'protection_ticks_remaining' => 0,
            'ai_enabled' => true,
            'ai_config' => [
                $currentTick + 1 => [
                    [
                        'action' => 'daily_bonus',
                        'key' => 'platinum',
                        'key2' => null,
                        'amount' => null,
                    ],
                ],
            ],
        ]);
    }

    public function testAutomationPageRendersTheUpgradedControls(): void
    {
        $this->visitRoute('dominion.bonuses.actions')
            ->seeStatusCode(200)
            ->see('Saved Templates')
            ->see('Quick fill')
            ->see('Copy complete tick');

        $this->assertCount(3, $this->crawler->filter('.automation-template'));
        $this->assertCount(1, $this->crawler->filter('#automation-studio-title .ra-robot-arm'));
        $this->assertCount(3, $this->crawler->filter('.automation-quota-track > i'));
        $this->assertCount(12, $this->crawler->filter('.automation-tick-row'));
        $this->assertCount(1, $this->crawler->filter('.automation-tick-row.is-occupied'));
        $this->assertCount(11, $this->crawler->filter('.automation-open-tick'));
        $this->assertCount(1, $this->crawler->filter('[id^="copyTickModal-"]'));
        $this->assertGreaterThan(0, $this->crawler->filter('[role="combobox"]')->count());
    }
}
