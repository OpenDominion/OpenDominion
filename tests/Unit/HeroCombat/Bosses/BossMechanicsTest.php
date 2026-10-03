<?php

namespace OpenDominion\Tests\Unit\HeroCombat\Bosses;

use OpenDominion\HeroCombat\Content\Encounters\PlanewalkerEncounter;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\EncounterContext;
use OpenDominion\HeroCombat\Engine\Effects\Hook;
use OpenDominion\HeroCombat\Engine\Stats\Stat;
use OpenDominion\HeroCombat\Engine\TurnResolver;
use OpenDominion\Tests\Unit\HeroCombat\Support\BattleBuilder;
use OpenDominion\Tests\Unit\HeroCombat\Support\BuildsBattles;
use OpenDominion\Tests\Unit\HeroCombat\Support\ScriptedRandomSource;
use PHPUnit\Framework\TestCase;

class BossMechanicsTest extends TestCase
{
    use BuildsBattles;

    private function encounter(string $key, ?ScriptedRandomSource $random = null): Battle
    {
        return BattleBuilder::make($key)->withRoster()->hero('Player')->build($random);
    }

    private function kill(Battle $battle, CombatantState $combatant): void
    {
        $combatant->currentHealth = 0;
        (new TurnResolver())->processDeaths($battle);
    }

    private function endTurn(Battle $battle, int $turn): void
    {
        $battle->state->turn = $turn;
        $battle->dispatcher->runEverywhere(Hook::TurnEnd, $battle);
    }

    private function forcedAbility(Battle $battle, CombatantState $combatant, int $turn): ?string
    {
        $battle->state->turn = $turn;

        return $battle->dispatcher->first(Hook::ForcedIntent, [$combatant], $combatant, $battle)?->abilityKey;
    }

    // ------------------------------------------------------------------
    // The Fallen Kings: Undying
    // ------------------------------------------------------------------

    public function testUndyingReturnsAtHalfHealthFiveTurnsLater(): void
    {
        $battle = $this->encounter('fallen_kings');
        $king = $this->named($battle, 'The Warrior King');

        $battle->state->turn = 3;
        $this->kill($battle, $king);
        $this->assertStringContainsString('will return from the dead in 5 turns', $battle->log->text());

        $this->endTurn($battle, 3);
        foreach ([4, 5, 6, 7] as $turn) {
            $this->endTurn($battle, $turn);
            $this->assertFalse($king->isAlive(), "Still dead after turn {$turn}");
        }

        $this->endTurn($battle, 8);
        $this->assertTrue($king->isAlive());
        $this->assertEquals(40, $king->currentHealth);
        $this->assertEquals(40, $battle->maxHealth($king));
        $this->assertStringContainsString('has returned to life', $battle->log->text());
    }

    public function testUndyingDoesNotKeepTheBattleGoingOnceTheWholeTeamFalls(): void
    {
        $battle = $this->encounter('fallen_kings');
        foreach ($battle->enemiesOf($this->named($battle, 'Player')) as $king) {
            $king->currentHealth = 0;
        }
        $this->queue($battle, 'Player', 'defend');

        $this->resolveTurn($battle);

        $this->assertTrue($battle->state->finished);
        $this->assertEquals(1, $battle->state->winningTeam);
    }

    // ------------------------------------------------------------------
    // The Eternal Guardian: Undying Legion + Necromancy
    // ------------------------------------------------------------------

    public function testGuardianSummonsOnTurnOneAndEveryFourthTurn(): void
    {
        $battle = $this->encounter('eternal_guardian');
        $guardian = $this->named($battle, 'The Eternal Guardian');

        $this->assertEquals('summon_skeleton', $this->forcedAbility($battle, $guardian, 1));
        $this->assertNull($this->forcedAbility($battle, $guardian, 2));
        $this->assertEquals('summon_skeleton', $this->forcedAbility($battle, $guardian, 5));

        $this->endTurn($battle, 4);
        $this->assertStringContainsString('summoning circle begins to glow', $battle->log->text());
    }

    public function testGuardianIsImmuneWhileMinionsLive(): void
    {
        $battle = $this->encounter('eternal_guardian');
        $guardian = $this->named($battle, 'The Eternal Guardian');
        $this->assertEquals(20, $battle->stat($guardian, Stat::Defense));

        $this->queue($battle, 'Player', 'defend');
        $this->resolveTurn($battle);
        $skeleton = $this->named($battle, 'Skeleton Warrior #1');

        $this->assertEquals(2, $skeleton->team);
        $this->assertEquals(999, $battle->stat($guardian, Stat::Defense));

        $skeleton->currentHealth = 0;
        $this->assertEquals(20, $battle->stat($guardian, Stat::Defense));
    }

    // ------------------------------------------------------------------
    // The Nightbringer: Darkness + Dying Light
    // ------------------------------------------------------------------

    public function testDarknessStacksOnOddTurnsAndDyingLightStripsIt(): void
    {
        $battle = $this->encounter('nightbringer');
        $nightbringer = $this->named($battle, 'The Nightbringer');

        $this->assertEquals('darkness', $this->forcedAbility($battle, $nightbringer, 1));
        $this->assertNull($this->forcedAbility($battle, $nightbringer, 2));

        $battle->state->turn = 1;
        $this->perform($battle, 'The Nightbringer', 'darkness');
        $this->perform($battle, 'The Nightbringer', 'darkness');
        $this->assertEquals(40, $battle->stat($nightbringer, Stat::Evasion));

        $this->kill($battle, $this->named($battle, 'Nox Cultist #1'));

        $this->assertEquals(0, $battle->stat($nightbringer, Stat::Evasion));
        $this->assertStringContainsString('exposing The Nightbringer', $battle->log->text());
    }

    public function testDarknessStopsAtFullEvasion(): void
    {
        $battle = $this->encounter('nightbringer');
        $nightbringer = $this->named($battle, 'The Nightbringer');
        $battle->effects->apply($nightbringer, 'shrouded', null, 5);

        $this->assertNull($this->forcedAbility($battle, $nightbringer, 3));
    }

    // ------------------------------------------------------------------
    // Planewalker: Void Rift, Wounded Retreat, grants, realm wounds
    // ------------------------------------------------------------------

    public function testPlanewalkerFallingCollapsesItsGolems(): void
    {
        $battle = $this->encounter('planewalker');
        $planewalker = $this->named($battle, 'The Planewalker');
        $battle->summon('golem', 2, 'Void Golem #1', $planewalker);
        $battle->summon('golem', 2, 'Void Golem #2', $planewalker);

        $this->kill($battle, $planewalker);

        $this->assertSame([], $battle->enemiesOf($this->named($battle, 'Player')));
        $this->assertStringContainsString('Void Constructs crumble', $battle->log->text());
        $this->assertStringContainsString('retreats across the planes', $battle->encounter->victoryMessage());
    }

    public function testPlanewalkerGrantsClassAbilities(): void
    {
        $encounter = new PlanewalkerEncounter();
        $player = $this->named(BattleBuilder::make()->hero('Player')->build(), 'Player');

        $this->assertEquals(['shadow_strike', 'demolish'], $encounter->playerGrants($player, ['infiltrator', 'engineer', 'farmer']));
        $this->assertSame([], $encounter->playerGrants($player, ['farmer']));
    }

    public function testRealmWoundsWeakenThePlanewalker(): void
    {
        $encounter = new PlanewalkerEncounter();

        $fresh = $encounter->roster(new EncounterContext(priorWins: 0))[0];
        $wounded = $encounter->roster(new EncounterContext(priorWins: 2))[0];
        $capped = $encounter->roster(new EncounterContext(priorWins: 9))[0];

        $this->assertArrayNotHasKey('stats', $fresh);
        $this->assertEquals(['health' => 160, 'evasion' => 40], $wounded['stats']);
        $this->assertEquals(['interval' => 6], $wounded['effects']['void_rift']);
        $this->assertEquals(['health' => 100, 'evasion' => 25], $capped['stats']);
    }

    public function testScaledSummonIntervalIsUsed(): void
    {
        $battle = BattleBuilder::make('planewalker')->hero('Player')->build();
        $battle->spawn($battle->registry->enemy('planewalker'), 2, 'The Planewalker', [], ['void_rift' => ['interval' => 6]]);
        $planewalker = $this->named($battle, 'The Planewalker');

        $this->assertEquals('summon_golem', $this->forcedAbility($battle, $planewalker, 7));
        $this->assertNull($this->forcedAbility($battle, $planewalker, 5));
    }

    // ------------------------------------------------------------------
    // The Wraith: Soul Rend
    // ------------------------------------------------------------------

    public function testWraithChargesSoulRendWhenWounded(): void
    {
        $battle = $this->encounter('wraith');
        $wraith = $this->named($battle, 'The Wraith');

        $this->endTurn($battle, 1);
        $this->assertNull($this->forcedAbility($battle, $wraith, 2), 'No charge while healthy');

        $wraith->currentHealth = 40;
        $this->endTurn($battle, 2);
        $this->assertStringContainsString('defend yourself', $battle->log->text());
        $this->assertEquals('soul_rend', $this->forcedAbility($battle, $wraith, 3));
        $this->assertNull($this->forcedAbility($battle, $wraith, 3), 'Fires once');

        $this->endTurn($battle, 3);
        $this->assertNull($this->forcedAbility($battle, $wraith, 4), 'Does not recharge without new damage');

        $wraith->currentHealth = 30;
        $this->endTurn($battle, 4);
        $this->assertEquals('soul_rend', $this->forcedAbility($battle, $wraith, 5));
    }

    public function testSoulRendIsBluntedByDefendingAndDevoursTheFallen(): void
    {
        $battle = $this->encounter('wraith');
        $this->declare($battle, 'Player', 'defend');

        $this->perform($battle, 'The Wraith', 'soul_rend', 'Player');
        $this->assertEquals(90, $this->named($battle, 'Player')->currentHealth, '(35 + 85) - (20 * 2 + 70) = 10 damage');

        $battle->state->intents = [];
        $battle->effects->removeByKey($this->named($battle, 'Player'), 'defending');
        $text = $this->perform($battle, 'The Wraith', 'soul_rend', 'Player');
        $this->assertEquals(0, $this->named($battle, 'Player')->currentHealth);
        $this->assertStringContainsString('devours their soul', $text);
    }

    // ------------------------------------------------------------------
    // Dreadsoul Skullkeeper: Soul Tribute
    // ------------------------------------------------------------------

    public function testFallenOrcsEmpowerTheSkullkeeper(): void
    {
        $battle = $this->encounter('dreadsoul_skullkeeper');
        $boss = $this->named($battle, 'Dreadsoul Skullkeeper');

        $this->kill($battle, $this->named($battle, 'Orc Boneguard'));
        $this->kill($battle, $this->named($battle, 'Orc Hexcaller'));

        $this->assertEquals(45, $battle->stat($boss, Stat::Attack));
        $this->assertEquals(25, $battle->stat($boss, Stat::Defense));
        $this->assertStringContainsString('He grows stronger', $battle->log->text());
    }

    public function testRealmWoundsWeakenTheSkullkeeper(): void
    {
        $roster = $this->encounter('dreadsoul_skullkeeper')->encounter->roster(new EncounterContext(priorWins: 5));

        $this->assertEquals(['health' => 135], $roster[0]['stats']);
        $this->assertCount(3, $roster);
    }

    // ------------------------------------------------------------------
    // The Dream of Thessadrash: Aspect Shift
    // ------------------------------------------------------------------

    public function testEachAspectGivesWayToTheNext(): void
    {
        $battle = $this->encounter('dream_of_thessadrash');

        $this->kill($battle, $this->named($battle, 'Veil of Thessadrash'));
        $shadow = $this->named($battle, 'Shadow of Thessadrash');
        $this->assertTrue($shadow->isAlive());
        $this->assertEquals(2, $shadow->team);

        $this->kill($battle, $shadow);
        $maw = $this->named($battle, 'Maw of Thessadrash');
        $this->assertTrue($maw->isAlive());
        $this->assertTrue($battle->effects->has($maw, 'hardiness'));
        $this->assertNull($battle->victoryCondition()->evaluate($battle), 'The fight goes on while a form remains');

        $this->assertStringContainsString('transforming into Maw of Thessadrash', $battle->log->text());
    }

    // ------------------------------------------------------------------
    // Rex Lunae: Curse of the Hungry Moon
    // ------------------------------------------------------------------

    public function testCleanseOnTheTurnItLandsPreventsTheCurse(): void
    {
        $battle = $this->encounter('rex_lunae');
        $this->declare($battle, 'Player', 'cleanse');

        $text = $this->perform($battle, 'Rex Lunae', 'hungering_moon', 'Player');

        $this->assertEquals(100, $battle->maxHealth($this->named($battle, 'Player')));
        $this->assertStringContainsString('removes the curse', $text);
    }

    public function testCurseTurnsThenConvertsTheHero(): void
    {
        $battle = $this->encounter('rex_lunae');
        $player = $this->named($battle, 'Player');

        $this->perform($battle, 'Rex Lunae', 'hungering_moon', 'Player');
        $this->assertEquals(66, $battle->maxHealth($player));
        $this->assertEquals('Player', $player->name);

        $text = $this->perform($battle, 'Rex Lunae', 'hungering_moon', 'Player');
        $this->assertEquals(32, $battle->maxHealth($player));
        $this->assertEquals('Servus Lunae', $player->name);
        $this->assertEquals(15, $battle->stat($player, Stat::Defense));
        $this->assertStringContainsString('Little of Player\'s humanity remains', $text);

        $text = $this->perform($battle, 'Rex Lunae', 'hungering_moon', 'Servus Lunae');
        $this->assertFalse($player->isAlive());
        $this->assertStringContainsString('joins the pack', $text);
    }

    public function testCurseCannotBeCleansedAfterTheFact(): void
    {
        $battle = $this->encounter('rex_lunae');
        $player = $this->named($battle, 'Player');
        $this->perform($battle, 'Rex Lunae', 'hungering_moon', 'Player');

        $this->perform($battle, 'Player', 'cleanse');

        $this->assertEquals(66, $battle->maxHealth($player));
    }

    public function testRexLunaeIsExposedWhileCasting(): void
    {
        $battle = $this->encounter('rex_lunae');
        $rex = $this->named($battle, 'Rex Lunae');

        $this->declare($battle, 'Rex Lunae', 'hungering_moon');

        $this->assertEquals(20, $battle->stat($rex, Stat::Defense));
    }

    public function testCurseLeavesAGapUnlessFrenzied(): void
    {
        $battle = $this->encounter('rex_lunae');
        $rex = $this->named($battle, 'Rex Lunae');
        $curse = $battle->effects->find($rex, 'hungering_moon_curse');

        $this->endTurn($battle, 1);
        $this->assertEquals('hungering_moon', $curse->data['pending']);
        $this->assertEquals('hungering_moon', $this->forcedAbility($battle, $rex, 2));

        $rex->lastAction = 'hungering_moon';
        $this->endTurn($battle, 2);
        $this->assertArrayNotHasKey('pending', $curse->data, 'Leaves a turn for Cleanse to come off cooldown');

        $rex->currentHealth = 79;
        $this->endTurn($battle, 3);
        $this->assertEquals('hungering_moon', $curse->data['pending'], 'Frenzied: back to back');
    }

    public function testRexLunaeGrantsCleanse(): void
    {
        $battle = $this->encounter('rex_lunae');

        $this->assertEquals(['cleanse'], $battle->encounter->playerGrants($this->named($battle, 'Player'), []));
    }
}
