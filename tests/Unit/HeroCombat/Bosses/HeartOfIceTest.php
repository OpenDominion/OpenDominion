<?php

namespace OpenDominion\Tests\Unit\HeroCombat\Bosses;

use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\Effects\Hook;
use OpenDominion\HeroCombat\Engine\Stats\Stat;
use OpenDominion\Tests\Unit\HeroCombat\Support\BattleBuilder;
use OpenDominion\Tests\Unit\HeroCombat\Support\BuildsBattles;
use PHPUnit\Framework\TestCase;

class HeartOfIceTest extends TestCase
{
    use BuildsBattles;

    protected Battle $battle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->battle = BattleBuilder::make('heart_of_ice')->withRoster()->hero('Player')->build();
    }

    protected function eliza(): \OpenDominion\HeroCombat\Engine\CombatantState
    {
        return $this->named($this->battle, 'Eliza, the Heart of Ice');
    }

    protected function player(): \OpenDominion\HeroCombat\Engine\CombatantState
    {
        return $this->named($this->battle, 'Player');
    }

    public function testBloodrendPunishesAttackingAndHealsEliza(): void
    {
        $this->eliza()->currentHealth = 100;
        $this->declare($this->battle, 'Player', 'attack');

        $text = $this->perform($this->battle, 'Eliza, the Heart of Ice', 'bloodrend', 'Player');

        $this->assertEquals(50, $this->player()->currentHealth);
        $this->assertEquals(150, $this->eliza()->currentHealth);
        $this->assertStringContainsString('wild swing', $text);
    }

    public function testBloodrendIsBluntedByDefending(): void
    {
        $this->eliza()->currentHealth = 100;
        $this->declare($this->battle, 'Player', 'defend');

        $this->perform($this->battle, 'Eliza, the Heart of Ice', 'bloodrend', 'Player');

        $this->assertEquals(85, $this->player()->currentHealth);
        $this->assertEquals(108, $this->eliza()->currentHealth);
    }

    public function testFrostGripIsShatteredByFocus(): void
    {
        $this->declare($this->battle, 'Player', 'focus');

        $text = $this->perform($this->battle, 'Eliza, the Heart of Ice', 'frost_grip', 'Player');

        $this->assertFalse($this->battle->effects->has($this->player(), 'freezing'));
        $this->assertStringContainsString('clarity of mind', $text);
    }

    public function testFrostGripFreezesNextTurnOnly(): void
    {
        $this->declare($this->battle, 'Player', 'defend');
        $this->perform($this->battle, 'Eliza, the Heart of Ice', 'frost_grip', 'Player');

        $this->assertTrue($this->battle->effects->has($this->player(), 'freezing'));
        $this->assertEquals(40, $this->battle->stat($this->player(), Stat::Attack), 'Not frozen during the turn Frost Grip lands');

        $this->battle->effects->tickTurnEnd();
        $this->assertTrue($this->battle->effects->has($this->player(), 'frozen'));
        $this->assertEquals(0, $this->battle->stat($this->player(), Stat::Attack));
        $this->assertEquals(0, $this->battle->stat($this->player(), Stat::Counter));
        $this->assertEquals(0, $this->battle->stat($this->player(), Stat::Recover));
        $this->assertEquals(20, $this->battle->stat($this->player(), Stat::Defense), 'Defense is untouched');

        $this->battle->effects->tickTurnEnd();
        $this->assertFalse($this->battle->effects->has($this->player(), 'frozen'));
        $this->assertEquals(40, $this->battle->stat($this->player(), Stat::Attack));
    }

    public function testFrostGripWhileRecoveringAddsFrostbite(): void
    {
        $this->declare($this->battle, 'Player', 'recover');

        $this->perform($this->battle, 'Eliza, the Heart of Ice', 'frost_grip', 'Player');

        $this->assertEquals(5, $this->battle->effects->stacks($this->player(), 'frostbitten'));
        $this->assertEquals(20 - 5 - 5, $this->battle->stat($this->player(), Stat::Defense), 'Frostbite stacks plus the recovering penalty');
    }

    public function testWintersBreathIsInterruptedByAttacking(): void
    {
        $this->declare($this->battle, 'Player', 'attack');

        $text = $this->perform($this->battle, 'Eliza, the Heart of Ice', 'winters_breath', 'Player');

        $this->assertEquals(100, $this->player()->currentHealth);
        $this->assertStringContainsString('interrupting', $text);
    }

    public function testWintersBreathPunishesDefending(): void
    {
        $this->declare($this->battle, 'Player', 'defend');

        $this->perform($this->battle, 'Eliza, the Heart of Ice', 'winters_breath', 'Player');

        $this->assertEquals(50, $this->player()->currentHealth);
    }

    public function testLandedAttacksStackFrostbiteOnHeroes(): void
    {
        $this->perform($this->battle, 'Eliza, the Heart of Ice', 'attack', 'Player');
        $text = $this->perform($this->battle, 'Eliza, the Heart of Ice', 'attack', 'Player');

        $this->assertEquals(2, $this->battle->effects->stacks($this->player(), 'frostbitten'));
        $this->assertStringContainsString('(stack 2)', $text);
    }

    public function testCurseTelegraphsOnOddTurnsAndFiresOnEven(): void
    {
        $curse = $this->battle->effects->find($this->eliza(), 'snow_witch_curse');

        $this->battle->state->turn = 2;
        $this->battle->dispatcher->runEverywhere(Hook::TurnEnd, $this->battle);
        $this->assertArrayNotHasKey('pending', $curse->data, 'No telegraph on even turns');

        $this->battle->state->turn = 1;
        $this->battle->dispatcher->runEverywhere(Hook::TurnEnd, $this->battle);
        $pending = $curse->data['pending'];
        $this->assertContains($pending, ['bloodrend', 'frost_grip', 'winters_breath']);

        $this->battle->state->turn = 2;
        $this->queue($this->battle, 'Player', 'defend');
        $this->resolveTurn($this->battle);

        $this->assertEquals($pending, $this->eliza()->lastAction);
        $this->assertArrayNotHasKey('pending', $curse->data);
    }
}
