<?php

namespace OpenDominion\Tests\Unit\HeroCombat;

use OpenDominion\HeroCombat\Content\Abilities\Recover;
use OpenDominion\HeroCombat\Content\Effects\Techniques\BattleFury;
use OpenDominion\HeroCombat\Engine\ActionValidator;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\Stats\Stat;
use OpenDominion\Tests\Unit\HeroCombat\Support\BattleBuilder;
use OpenDominion\Tests\Unit\HeroCombat\Support\BuildsBattles;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Guard, Exposed on repeat Defend, Recover charges, Rally and Counter through a guard, plus
 * the rule that nothing changes a combatant's stats partway through a turn.
 */
class CombatRulesTest extends TestCase
{
    use BuildsBattles;

    /**
     * @return array<string, array{0: bool}>
     */
    public static function teamOrders(): array
    {
        return ['first team acts first' => [true], 'second team acts first' => [false]];
    }

    private function teams(bool $firstTeamFirst = true): Battle
    {
        $builder = BattleBuilder::make();

        return $firstTeamFirst
            ? $builder->hero('Hero')->hero('Ally')->hero('Foe', team: 2)->hero('Other', team: 2)->build()
            : $builder->hero('Foe', team: 2)->hero('Other', team: 2)->hero('Hero')->hero('Ally')->build();
    }

    private function duel(): Battle
    {
        return BattleBuilder::make()->hero('Hero')->hero('Foe', team: 2)->build();
    }

    /**
     * @param array<string, array{0: string, 1?: string}> $orders name => [ability, target]
     */
    private function turn(Battle $battle, array $orders): void
    {
        foreach ($orders as $name => $order) {
            $this->queue($battle, $name, $order[0], $order[1] ?? null);
        }
        $this->resolveTurn($battle);
    }

    private function health(Battle $battle, string $name): int
    {
        return $this->named($battle, $name)->currentHealth;
    }

    public function testGuardLeavesTheGuardianAtThirtyDefenseAndCoversBothHits(): void
    {
        $battle = $this->teams();

        $this->turn($battle, [
            'Hero' => ['defend', 'Ally'],
            'Ally' => ['focus'],
            'Foe' => ['attack', 'Ally'],
            'Other' => ['attack', 'Hero'],
        ]);

        $this->assertEquals(100, $this->health($battle, 'Ally'));
        $this->assertEquals(80, $this->health($battle, 'Hero'));
    }

    public function testGuardBonusScalesWithDefense(): void
    {
        $battle = BattleBuilder::make()->hero('Hero', stats: ['defense' => 30])->hero('Ally')->hero('Foe', team: 2, stats: ['attack' => 60])->build();

        $this->turn($battle, ['Hero' => ['defend', 'Ally'], 'Ally' => ['focus'], 'Foe' => ['attack', 'Ally']]);

        $this->assertEquals(100 - (60 - 45), $this->health($battle, 'Hero'));
    }

    public function testRepeatingGuardHasNoPenalty(): void
    {
        $battle = $this->teams();
        $guard = ['Hero' => ['defend', 'Ally'], 'Ally' => ['attack', 'Foe'], 'Foe' => ['attack', 'Ally'], 'Other' => ['focus']];

        $this->turn($battle, $guard);
        $this->turn($battle, ['Hero' => ['defend', 'Ally'], 'Ally' => ['attack', 'Foe'], 'Foe' => ['attack', 'Ally'], 'Other' => ['defend']]);

        $this->assertEquals(80, $this->health($battle, 'Hero'), 'Two hits at 30 defense');
    }

    public function testMutualGuardSwapsTheHitsAndBothKeepTheBonus(): void
    {
        $battle = $this->teams();
        $this->named($battle, 'Foe')->baseStats[Stat::Attack->value] = 50;

        $this->turn($battle, [
            'Hero' => ['defend', 'Ally'],
            'Ally' => ['defend', 'Hero'],
            'Foe' => ['attack', 'Hero'],
            'Other' => ['attack', 'Ally'],
        ]);

        $this->assertEquals(90, $this->health($battle, 'Hero'), 'The 40 attack aimed at Ally, against 30 defense');
        $this->assertEquals(80, $this->health($battle, 'Ally'), 'The 50 attack aimed at Hero, against 30 defense');
    }

    public function testGuardIsRecordedSeparatelyFromDefend(): void
    {
        $battle = $this->teams();

        $this->turn($battle, ['Hero' => ['defend', 'Ally'], 'Ally' => ['defend'], 'Foe' => ['focus'], 'Other' => ['focus']]);

        $this->assertEquals('guard', $this->named($battle, 'Hero')->lastAction);
        $this->assertEquals('defend', $this->named($battle, 'Ally')->lastAction);
    }

    public function testRepeatDefendDropsToThirtyFiveDefense(): void
    {
        $battle = $this->duel();

        $this->turn($battle, ['Hero' => ['defend'], 'Foe' => ['attack', 'Hero']]);
        $this->assertEquals(100, $this->health($battle, 'Hero'), 'First Defend blocks an attack entirely');

        $this->turn($battle, ['Hero' => ['defend'], 'Foe' => ['attack', 'Hero']]);
        $this->assertEquals(95, $this->health($battle, 'Hero'), 'Exposed: 20 x (2 - 0.25) = 35 defense');

        $this->turn($battle, ['Hero' => ['defend'], 'Foe' => ['attack', 'Hero']]);
        $this->assertEquals(90, $this->health($battle, 'Hero'), 'The streak keeps it Exposed');
    }

    public function testAnotherActionClearsTheDefendStreak(): void
    {
        $battle = $this->duel();

        $this->turn($battle, ['Hero' => ['defend'], 'Foe' => ['counter']]);
        $this->turn($battle, ['Hero' => ['focus'], 'Foe' => ['focus']]);
        $this->turn($battle, ['Hero' => ['defend'], 'Foe' => ['attack', 'Hero']]);

        $this->assertEquals(90, $this->health($battle, 'Hero'), 'Focused 50 against a full 40 defense');
    }

    public function testGuardBetweenDefendsAvoidsExposed(): void
    {
        $battle = $this->teams();

        $this->turn($battle, ['Hero' => ['defend'], 'Ally' => ['focus'], 'Foe' => ['focus'], 'Other' => ['focus']]);
        $this->turn($battle, ['Hero' => ['defend', 'Ally'], 'Ally' => ['counter'], 'Foe' => ['counter'], 'Other' => ['counter']]);
        $this->turn($battle, ['Hero' => ['defend'], 'Ally' => ['focus'], 'Foe' => ['attack', 'Hero'], 'Other' => ['attack', 'Hero']]);

        $this->assertEquals(80, $this->health($battle, 'Hero'), 'Focused 50 against 40 defense, twice');
    }

    public function testRecoverHasThreeChargesSharedWithHealingAnAlly(): void
    {
        $battle = $this->teams();
        $hero = $this->named($battle, 'Hero');
        $hero->currentHealth = 10;
        $this->named($battle, 'Ally')->currentHealth = 10;
        $idle = ['Foe' => ['defend'], 'Other' => ['defend']];

        $this->turn($battle, ['Hero' => ['recover'], 'Ally' => ['defend']] + $idle);
        $this->turn($battle, ['Hero' => ['defend'], 'Ally' => ['defend']] + $idle);
        $this->turn($battle, ['Hero' => ['recover', 'Ally'], 'Ally' => ['defend']] + $idle);
        $this->turn($battle, ['Hero' => ['defend'], 'Ally' => ['defend']] + $idle);
        $this->turn($battle, ['Hero' => ['recover'], 'Ally' => ['defend']] + $idle);
        $this->turn($battle, ['Hero' => ['defend'], 'Ally' => ['defend']] + $idle);

        $validator = new ActionValidator($battle);
        $this->assertSame(0, $validator->chargesRemaining($hero, 'recover'));
        $this->assertFalse($validator->canPerform($hero, 'recover'));
        $this->assertSame(Recover::MAX_CHARGES, $validator->chargesRemaining($this->named($battle, 'Ally'), 'recover'));
    }

    public function testEnemiesHaveRecoverChargesToo(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->npc('Foe')->build();
        $foe = $this->named($battle, 'Foe');

        (new ActionValidator($battle))->recordUse($foe, $battle->registry->ability('recover'));

        $this->assertSame(Recover::MAX_CHARGES - 1, (new ActionValidator($battle))->chargesRemaining($foe, 'recover'));
    }

    public function testRallyAddsTheCastersFocusToTheAllysAttackThatTurn(): void
    {
        $battle = BattleBuilder::make()->hero('Hero', stats: ['focus' => 16])->hero('Ally')->hero('Foe', team: 2)->build();

        $this->turn($battle, ['Hero' => ['focus', 'Ally'], 'Ally' => ['attack', 'Foe'], 'Foe' => ['focus']]);

        $this->assertEquals(100 - (40 + 16 - 20), $this->health($battle, 'Foe'));
    }

    #[DataProvider('teamOrders')]
    public function testRallyWorksWhoeverActsFirst(bool $firstTeamFirst): void
    {
        $battle = $this->teams($firstTeamFirst);

        $this->turn($battle, ['Ally' => ['focus', 'Hero'], 'Hero' => ['attack', 'Foe'], 'Foe' => ['focus'], 'Other' => ['focus']]);

        $this->assertEquals(70, $this->health($battle, 'Foe'));
    }

    public function testRallyStacksWithTheAllysOwnFocus(): void
    {
        $battle = $this->teams();

        $this->turn($battle, ['Hero' => ['focus'], 'Ally' => ['defend'], 'Foe' => ['defend'], 'Other' => ['defend']]);
        $this->turn($battle, ['Hero' => ['attack', 'Foe'], 'Ally' => ['focus', 'Hero'], 'Foe' => ['focus'], 'Other' => ['focus']]);

        $this->assertEquals(60, $this->health($battle, 'Foe'), '40 + 10 Focus + 10 Rally against 20 defense');
    }

    public function testRallyIsGoneNextTurnAndWastedWithoutAnAttack(): void
    {
        $battle = $this->teams();

        $this->turn($battle, ['Hero' => ['focus', 'Ally'], 'Ally' => ['defend'], 'Foe' => ['focus'], 'Other' => ['focus']]);
        $this->assertFalse($battle->effects->has($this->named($battle, 'Ally'), 'rallied'));

        $this->turn($battle, ['Hero' => ['defend'], 'Ally' => ['attack', 'Foe'], 'Foe' => ['defend'], 'Other' => ['defend']]);
        $this->assertEquals(100, $this->health($battle, 'Foe'), 'A plain attack against Defending');
    }

    public function testRallyDoesNotBoostCounters(): void
    {
        $battle = $this->teams();

        $this->turn($battle, ['Hero' => ['focus', 'Ally'], 'Ally' => ['counter'], 'Foe' => ['attack', 'Ally'], 'Other' => ['focus']]);

        $this->assertEquals(70, $this->health($battle, 'Foe'), '40 + 10 Counter against 20 defense');
    }

    public function testRallySharesFocusCooldown(): void
    {
        $battle = $this->teams();

        $this->turn($battle, ['Hero' => ['focus', 'Ally'], 'Ally' => ['defend'], 'Foe' => ['defend'], 'Other' => ['defend']]);

        $this->assertFalse((new ActionValidator($battle))->canPerform($this->named($battle, 'Hero'), 'focus'));
    }

    public function testFocusedHeroCanStillRallyButNotFocusAgain(): void
    {
        $battle = $this->teams();
        $hero = $this->named($battle, 'Hero');
        $battle->effects->apply($hero, 'focused');
        $ability = $battle->registry->ability('focus');

        $this->assertTrue((new ActionValidator($battle))->canPerform($hero, 'focus'));
        $this->assertFalse($battle->targets->isValidChoice($hero, $ability, null));
        $this->assertFalse($battle->targets->isValidChoice($hero, $ability, $hero));
        $this->assertTrue($battle->targets->isValidChoice($hero, $ability, $this->named($battle, 'Ally')));
    }

    public function testSoloFocusedHeroCannotUseFocus(): void
    {
        $battle = $this->duel();
        $hero = $this->named($battle, 'Hero');
        $battle->effects->apply($hero, 'focused');

        $this->assertFalse((new ActionValidator($battle))->canPerform($hero, 'focus'));
    }

    public function testAiKeepsFocusOnItselfUnlessAlreadyFocused(): void
    {
        $battle = BattleBuilder::make()->npc('Foe', ai: 'aggressive')->npc('Other', ai: 'aggressive')->hero('Hero', team: 1)->build();
        $foe = $this->named($battle, 'Foe');
        $strategy = new class('test', 'Test', ['focus' => 1]) extends \OpenDominion\HeroCombat\Content\Ai\WeightedStrategy {
        };

        $this->assertEquals($foe->id, $strategy->chooseIntent($foe, $battle)->targetId);

        $battle->effects->apply($foe, 'focused');

        $this->assertEquals($this->named($battle, 'Other')->id, $strategy->chooseIntent($foe, $battle)->targetId);
    }

    public function testCounterFiresThroughAGuard(): void
    {
        $battle = $this->teams();

        $this->turn($battle, ['Hero' => ['defend', 'Ally'], 'Ally' => ['counter'], 'Foe' => ['attack', 'Ally'], 'Other' => ['focus']]);

        $this->assertEquals(100, $this->health($battle, 'Ally'));
        $this->assertEquals(90, $this->health($battle, 'Hero'));
        $this->assertEquals(70, $this->health($battle, 'Foe'));
        $this->assertStringContainsString('Ally counters for 30 damage', $battle->log->text());
    }

    public function testCounterRetaliationIsNotTakenByTheAttackersGuard(): void
    {
        $battle = $this->teams();

        $this->turn($battle, ['Hero' => ['attack', 'Foe'], 'Ally' => ['defend', 'Hero'], 'Foe' => ['counter'], 'Other' => ['focus']]);

        $this->assertEquals(70, $this->health($battle, 'Hero'));
        $this->assertEquals(100, $this->health($battle, 'Ally'));
    }

    #[DataProvider('teamOrders')]
    public function testBattleFuryDefenseOnlyDropsAfterTheTurn(bool $firstTeamFirst): void
    {
        $battle = $this->teams($firstTeamFirst);
        $hero = $this->named($battle, 'Hero');
        $battle->effects->apply($hero, 'battle_fury');

        $this->turn($battle, ['Hero' => ['attack', 'Other'], 'Ally' => ['defend'], 'Foe' => ['attack', 'Hero'], 'Other' => ['defend']]);

        $this->assertEquals(80, $hero->currentHealth);
        $this->assertEquals(20 - BattleFury::DEFENSE_PENALTY, $battle->stat($hero, Stat::Defense));
    }

    #[DataProvider('teamOrders')]
    public function testSpellbrandMarksFromTheNextTurn(bool $firstTeamFirst): void
    {
        $battle = $this->teams($firstTeamFirst);
        $hero = $this->named($battle, 'Hero');
        $battle->effects->apply($hero, 'spellbrand');
        $battle->effects->apply($hero, 'focused');

        $this->turn($battle, ['Hero' => ['attack', 'Foe'], 'Ally' => ['attack', 'Foe'], 'Foe' => ['focus'], 'Other' => ['defend']]);
        $this->assertEquals(50, $this->health($battle, 'Foe'), '30 and 20, no Mark bonus this turn');
        $this->assertTrue($battle->effects->has($this->named($battle, 'Foe'), 'marked'));

        $this->turn($battle, ['Hero' => ['defend'], 'Ally' => ['attack', 'Foe'], 'Foe' => ['counter'], 'Other' => ['defend']]);
        $this->assertEquals(20, $this->health($battle, 'Foe'), '20 + 10 Mark');
    }

    #[DataProvider('teamOrders')]
    public function testForgeRaisesAttackFromTheNextTurn(bool $firstTeamFirst): void
    {
        $battle = $this->teams($firstTeamFirst);
        $hero = $this->named($battle, 'Hero');
        $hero->abilities[] = 'forge';

        $this->turn($battle, ['Hero' => ['forge'], 'Ally' => ['defend'], 'Foe' => ['counter'], 'Other' => ['defend']]);

        $this->assertEquals(40 + 4, $battle->stat($hero, Stat::Attack));
    }

    #[DataProvider('teamOrders')]
    public function testCombatAnalysisDoesNotWeakenTheTargetMidTurn(bool $firstTeamFirst): void
    {
        $battle = $this->teams($firstTeamFirst);
        $this->named($battle, 'Hero')->abilities[] = 'combat_analysis';

        $this->turn($battle, ['Hero' => ['combat_analysis', 'Foe'], 'Ally' => ['attack', 'Foe'], 'Foe' => ['focus'], 'Other' => ['defend']]);

        $this->assertEquals(80, $this->health($battle, 'Foe'));
        $this->assertEquals(20 - 4, $battle->stat($this->named($battle, 'Foe'), Stat::Defense));
    }
}
