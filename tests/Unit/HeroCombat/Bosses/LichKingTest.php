<?php

namespace OpenDominion\Tests\Unit\HeroCombat\Bosses;

use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\Effects\Hook;
use OpenDominion\HeroCombat\Engine\Stats\Stat;
use OpenDominion\Tests\Unit\HeroCombat\Support\BattleBuilder;
use OpenDominion\Tests\Unit\HeroCombat\Support\BuildsBattles;
use PHPUnit\Framework\TestCase;

class LichKingTest extends TestCase
{
    use BuildsBattles;

    protected Battle $battle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->battle = BattleBuilder::make('lich_king')->withRoster()->hero('Player')->build();
    }

    protected function lich(): \OpenDominion\HeroCombat\Engine\CombatantState
    {
        return $this->named($this->battle, 'Lich King');
    }

    protected function tome(): \OpenDominion\HeroCombat\Engine\CombatantState
    {
        return $this->named($this->battle, 'Tome of Power');
    }

    protected function endTurn(int $turn): void
    {
        $this->battle->state->turn = $turn;
        $this->battle->dispatcher->runEverywhere(Hook::TurnEnd, $this->battle);
    }

    /**
     * @return string[]
     */
    protected function effectKeysOn(\OpenDominion\HeroCombat\Engine\CombatantState $combatant): array
    {
        return array_map(fn ($pair) => $pair[1]->key, $this->battle->effects->applicableTo($combatant));
    }

    public function testChaptersRotateEveryThreeTurns(): void
    {
        $this->endTurn(1);
        $this->assertContains('lifesteal', $this->effectKeysOn($this->lich()));
        $this->assertContains('weakened', $this->effectKeysOn($this->tome()));
        $this->assertStringContainsString('Chapter of Blood', $this->battle->log->text());

        $this->endTurn(2);
        $this->endTurn(3);
        $this->assertContains('lifesteal', $this->effectKeysOn($this->lich()), 'Still the first chapter');

        $this->endTurn(4);
        $this->assertNotContains('lifesteal', $this->effectKeysOn($this->lich()));
        $this->assertContains('arcane_shield', $this->effectKeysOn($this->lich()));
        $this->assertEquals(40, $this->battle->stat($this->lich(), Stat::Defense));
        $this->assertContains('elusive', $this->effectKeysOn($this->tome()));
        $this->assertNotContains('weakened', $this->effectKeysOn($this->tome()));
    }

    public function testChaptersCycleBackToTheFirst(): void
    {
        foreach ([1, 4, 7, 10, 13] as $turn) {
            $this->endTurn($turn);
        }

        $this->assertContains('lifesteal', $this->effectKeysOn($this->lich()));
        $this->assertNotContains('retribution', $this->effectKeysOn($this->lich()));
    }

    public function testAuraDoesNotApplyToTheTomeItselfOrThePlayers(): void
    {
        $this->endTurn(1);

        $this->assertNotContains('lifesteal', $this->effectKeysOn($this->tome()));
        $this->assertNotContains('lifesteal', $this->effectKeysOn($this->named($this->battle, 'Player')));
    }

    public function testLichKingHealsFromLifestealAura(): void
    {
        $this->endTurn(1);
        $this->lich()->currentHealth = 100;

        $this->perform($this->battle, 'Lich King', 'attack', 'Player');

        $this->assertEquals(105, $this->lich()->currentHealth, '10 damage dealt heals 5');
    }

    public function testDestroyingTheTomeSeversTheLichKingAndEndsTheChapter(): void
    {
        $this->endTurn(4);
        $this->assertEquals(40, $this->battle->stat($this->lich(), Stat::Defense));

        $this->tome()->currentHealth = 0;
        $this->resolveDeaths();

        $this->assertEquals(20, $this->battle->stat($this->lich(), Stat::Defense), 'Arcane shield gone, defense -10');
        $this->assertEquals(0, $this->battle->stat($this->lich(), Stat::Evasion));
        $this->assertStringContainsString('crumbles to dust', $this->battle->log->text());

        $this->endTurn(7);
        $this->assertNotContains('crushing_blow', $this->effectKeysOn($this->lich()), 'A destroyed tome turns no more pages');
    }

    public function testEnrageAtLowHealth(): void
    {
        $this->lich()->currentHealth = 40;

        $this->assertEquals(40, $this->battle->stat($this->lich(), Stat::Attack));
    }

    private function resolveDeaths(): void
    {
        (new \OpenDominion\HeroCombat\Engine\TurnResolver())->processDeaths($this->battle);
    }
}
