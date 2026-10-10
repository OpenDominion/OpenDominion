<?php

namespace OpenDominion\Tests\Unit\HeroCombat\Bosses;

use OpenDominion\HeroCombat\Engine\ActionValidator;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Intent;
use OpenDominion\HeroCombat\Engine\IntentDecider;
use OpenDominion\Tests\Unit\HeroCombat\Support\BattleBuilder;
use OpenDominion\Tests\Unit\HeroCombat\Support\BuildsBattles;
use PHPUnit\Framework\TestCase;

class GrandMagisterTest extends TestCase
{
    use BuildsBattles;

    protected Battle $battle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->battle = BattleBuilder::make('grand_magister')->withRoster()->hero('Player')->build();
        $this->battle->state->turn = 1;
    }

    protected function magister(): CombatantState
    {
        return $this->named($this->battle, 'Grand Magister');
    }

    protected function player(): CombatantState
    {
        return $this->named($this->battle, 'Player');
    }

    protected function validator(): ActionValidator
    {
        return new ActionValidator($this->battle);
    }

    public function testMagicWardHalvesDamageTaken(): void
    {
        $this->perform($this->battle, 'Grand Magister', 'magic_ward');
        $this->perform($this->battle, 'Player', 'attack', 'Grand Magister');

        $this->assertEquals(200 - 10, $this->magister()->currentHealth, '(40 attack - 20 defense) halved');
    }

    public function testMagicWardLastsForTheNextThreeTurns(): void
    {
        $this->perform($this->battle, 'Grand Magister', 'magic_ward');

        foreach (['first', 'second', 'third'] as $turn) {
            $this->battle->effects->tickTurnEnd();
            $this->assertTrue($this->battle->effects->has($this->magister(), 'magic_ward'), "Active on the {$turn} turn after the cast");
        }

        $this->battle->effects->tickTurnEnd();
        $this->assertFalse($this->battle->effects->has($this->magister(), 'magic_ward'));

        $this->perform($this->battle, 'Player', 'attack', 'Grand Magister');
        $this->assertEquals(200 - 20, $this->magister()->currentHealth);
    }

    public function testBacklashReflectsHalfTheDamageWithoutReducingIt(): void
    {
        $this->perform($this->battle, 'Grand Magister', 'backlash');

        $text = $this->perform($this->battle, 'Player', 'attack', 'Grand Magister');

        $this->assertEquals(200 - 20, $this->magister()->currentHealth, 'The Magister takes the full hit');
        $this->assertEquals(100 - 10, $this->player()->currentHealth, 'Half of 20 rebounds exactly once');
        $this->assertStringContainsString('Backlash rebounds on Player', $text);
    }

    public function testBacklashLastsForTheNextTwoTurns(): void
    {
        $this->perform($this->battle, 'Grand Magister', 'backlash');

        $this->battle->effects->tickTurnEnd();
        $this->assertTrue($this->battle->effects->has($this->magister(), 'backlash'), 'Active on the next turn');

        $this->battle->effects->tickTurnEnd();
        $this->assertTrue($this->battle->effects->has($this->magister(), 'backlash'), 'Active on the turn after that');

        $this->battle->effects->tickTurnEnd();
        $this->assertFalse($this->battle->effects->has($this->magister(), 'backlash'));
    }

    public function testBacklashReflectsOnlyTheDamageThatLands(): void
    {
        $this->player()->baseStats['attack'] = 50;
        $this->perform($this->battle, 'Grand Magister', 'backlash');
        $this->declare($this->battle, 'Grand Magister', 'defend');

        $this->perform($this->battle, 'Player', 'attack', 'Grand Magister');

        $this->assertEquals(200 - 10, $this->magister()->currentHealth, '50 attack - 40 defending defense');
        $this->assertEquals(100 - 5, $this->player()->currentHealth);
    }

    public function testSilenceBlocksFocusAndRecoverForTheNextTwoTurns(): void
    {
        $this->perform($this->battle, 'Grand Magister', 'silence', 'Player');

        $this->assertTrue($this->battle->effects->has($this->player(), 'silenced'));
        $this->assertFalse($this->validator()->canPerform($this->player(), 'focus'));
        $this->assertFalse($this->validator()->canPerform($this->player(), 'recover'));
        $this->assertTrue($this->validator()->canPerform($this->player(), 'attack'));
        $this->assertTrue($this->validator()->canPerform($this->player(), 'defend'));
        $this->assertTrue($this->validator()->canPerform($this->player(), 'counter'));

        $this->battle->effects->tickTurnEnd();
        $this->assertFalse($this->validator()->canPerform($this->player(), 'focus'), 'Still silenced on the next turn');

        $this->battle->effects->tickTurnEnd();
        $this->assertFalse($this->validator()->canPerform($this->player(), 'recover'), 'Still silenced on the turn after that');

        $this->battle->effects->tickTurnEnd();
        $this->assertTrue($this->validator()->canPerform($this->player(), 'focus'));
        $this->assertTrue($this->validator()->canPerform($this->player(), 'recover'));
    }

    public function testSilencedQueuedFocusFallsBackToAnotherAction(): void
    {
        $this->perform($this->battle, 'Grand Magister', 'silence', 'Player');
        $this->queue($this->battle, 'Player', 'focus');

        $intent = (new IntentDecider($this->battle, $this->validator()))->decide($this->player());

        $this->assertNotEquals('focus', $intent->abilityKey);
        $this->assertNotEquals('recover', $intent->abilityKey);
    }

    public function testArcaneConduitForcesAnAttackOnlyOnTheFollowingTurn(): void
    {
        $decider = new IntentDecider($this->battle, $this->validator());
        $this->perform($this->battle, 'Grand Magister', 'arcane_conduit');

        $this->assertNotEquals(Intent::SOURCE_FORCED, $decider->decide($this->magister())->source, 'Not forced on the cast turn');

        $this->battle->effects->tickTurnEnd();
        $this->battle->state->turn = 2;

        $intent = $decider->decide($this->magister());
        $this->assertEquals(Intent::SOURCE_FORCED, $intent->source);
        $this->assertEquals('attack', $intent->abilityKey);
    }

    public function testArcaneConduitDoublesTheNextAttackAndIsConsumed(): void
    {
        $this->perform($this->battle, 'Grand Magister', 'arcane_conduit');
        $this->battle->effects->tickTurnEnd();
        $this->battle->state->turn = 2;

        $this->queue($this->battle, 'Player', 'focus');
        $this->resolveTurn($this->battle);

        $this->assertEquals(100 - 40, $this->player()->currentHealth, '(40 attack - 20 defense) x2');
        $this->assertFalse($this->battle->effects->has($this->magister(), 'arcane_conduit'));
    }

    public function testDefendingAnswersArcaneConduit(): void
    {
        $this->perform($this->battle, 'Grand Magister', 'arcane_conduit');
        $this->battle->effects->tickTurnEnd();
        $this->battle->state->turn = 2;

        $this->queue($this->battle, 'Player', 'defend');
        $this->resolveTurn($this->battle);

        $this->assertEquals(100, $this->player()->currentHealth, '40 attack against 40 defending defense');
        $this->assertFalse($this->battle->effects->has($this->magister(), 'arcane_conduit'));
    }

    public function testFocusedArcaneConduitBreaksThroughDefend(): void
    {
        $this->perform($this->battle, 'Grand Magister', 'focus');
        $this->perform($this->battle, 'Grand Magister', 'arcane_conduit');
        $this->battle->effects->tickTurnEnd();
        $this->battle->state->turn = 2;

        $this->queue($this->battle, 'Player', 'defend');
        $this->resolveTurn($this->battle);

        $this->assertEquals(100 - 40, $this->player()->currentHealth, '((40 attack + 20 focus) - 40 defending defense) x2');
        $this->assertFalse($this->battle->effects->has($this->magister(), 'focused'));
        $this->assertFalse($this->battle->effects->has($this->magister(), 'arcane_conduit'));
    }

    public function testMagisterAiCanChooseFocus(): void
    {
        $weights = $this->battle->registry->strategy('grand_magister')->weights();

        $this->assertArrayHasKey('focus', $weights);
        $this->assertArrayNotHasKey('defend', $weights);
        $this->assertArrayNotHasKey('counter', $weights);
    }

    public function testSelfBuffsCannotBeRecastWhileActiveOrOnCooldown(): void
    {
        foreach (['magic_ward', 'backlash', 'arcane_conduit'] as $spell) {
            $this->assertTrue($this->validator()->canPerform($this->magister(), $spell), "{$spell} starts ready");

            $this->perform($this->battle, 'Grand Magister', $spell);
            $this->assertFalse($this->validator()->canPerform($this->magister(), $spell), "{$spell} blocked while active");

            $this->validator()->recordUse($this->magister(), $this->battle->registry->ability($spell));
            $this->battle->effects->removeByKey($this->magister(), $spell);
            $this->assertFalse($this->validator()->canPerform($this->magister(), $spell), "{$spell} on cooldown");

            $this->battle->state->turn += 4;
            $this->assertTrue($this->validator()->canPerform($this->magister(), $spell), "{$spell} ready after cooldown");
        }
    }
}
