<?php

namespace OpenDominion\Tests\Unit\Services\Dominion;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Helpers\HeroEncounterHelper;
use OpenDominion\Helpers\HeroHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hero;
use OpenDominion\Models\HeroBattle;
use OpenDominion\Models\HeroBattleAction;
use OpenDominion\Models\HeroCombatant;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\HeroBattleService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class HeroBattleServiceTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var HeroBattleService */
    protected $heroBattleService;

    /** @var HeroHelper */
    protected $heroHelper;

    /** @var HeroEncounterHelper */
    protected $heroEncounterHelper;

    /** @var Round */
    protected $round;

    /** @var Dominion */
    protected $dominion;

    /** @var HeroBattle */
    protected $battle;

    /** @var HeroCombatant */
    protected $player;

    /** @var HeroCombatant */
    protected $varos;

    protected function setUp(): void
    {
        parent::setUp();

        $user = $this->createAndImpersonateUser();
        $this->round = $this->createRound('-3 days midnight');
        $this->dominion = $this->createDominionWithLegacyStats($user, $this->round, Race::where('name', 'Human')->firstOrFail());

        Hero::create([
            'dominion_id' => $this->dominion->id,
            'name' => 'Test Hero',
            'class' => 'blacksmith',
            'experience' => 2000,
            'class_data' => [],
        ]);
        $this->dominion->refresh();

        $this->heroBattleService = $this->app->make(HeroBattleService::class);
        $this->heroHelper = $this->app->make(HeroHelper::class);
        $this->heroEncounterHelper = $this->app->make(HeroEncounterHelper::class);

        $this->battle = HeroBattle::create(['round_id' => $this->round->id, 'pvp' => false]);
        $this->player = $this->heroBattleService->createCombatant($this->battle, $this->dominion->hero);
        $this->varos = $this->heroBattleService->createNonPlayerCombatant(
            $this->battle,
            $this->heroEncounterHelper->getEnemies()->get('admiral_varos')
        );
    }

    /**
     * Add n Aurelis Defenders to the battle.
     *
     * @return \Illuminate\Support\Collection<int, HeroCombatant>
     */
    protected function addDefenders(int $count)
    {
        $defenders = collect();
        for ($i = 0; $i < $count; $i++) {
            $defenders->push($this->heroBattleService->createNonPlayerCombatant(
                $this->battle,
                $this->heroEncounterHelper->getEnemies()->get('aurelis_defender')
            ));
        }

        return $defenders;
    }

    protected function actionDef(string $key): array
    {
        return $this->heroHelper->getCombatActions()->get($key);
    }

    /** Re-read Varos with fresh relations so battle.combatants is current. */
    protected function freshVaros(): HeroCombatant
    {
        return HeroCombatant::with('battle.combatants')->find($this->varos->id);
    }

    // ------------------------------------------------------------------
    // Broadside
    // ------------------------------------------------------------------

    public function testBroadside_PlayerDefends_TakesReducedDamage()
    {
        // Arrange
        $this->player->current_action = 'defend';

        // Act
        $result = $this->heroBattleService->processBroadsideAction(
            $this->freshVaros(),
            $this->player,
            $this->actionDef('broadside')
        );

        // Assert
        $this->assertEquals(15, $result['damage']);
        $this->assertStringContainsString('drops behind the stonework', $result['description']);
    }

    public function testBroadside_PlayerCounters_TakesFullVolley()
    {
        // Arrange - Counter is the trap: strong against blade_flurry, worst here
        $this->player->current_action = 'counter';

        // Act
        $result = $this->heroBattleService->processBroadsideAction(
            $this->freshVaros(),
            $this->player,
            $this->actionDef('broadside')
        );

        // Assert
        $this->assertEquals(45, $result['damage']);
        $this->assertStringContainsString('a blade that never comes', $result['description']);
    }

    public function testBroadside_PlayerAttacks_TakesDefaultDamage()
    {
        // Arrange
        $this->player->current_action = 'attack';

        // Act
        $result = $this->heroBattleService->processBroadsideAction(
            $this->freshVaros(),
            $this->player,
            $this->actionDef('broadside')
        );

        // Assert
        $this->assertEquals(30, $result['damage']);
    }

    public function testBroadside_KillsEveryDefenderButNotVaros()
    {
        // Arrange - a full quay
        $defenders = $this->addDefenders(2);
        $this->player->current_action = 'defend';
        $varosHealthBefore = $this->varos->current_health;

        // Act
        $result = $this->heroBattleService->processBroadsideAction(
            $this->freshVaros(),
            $this->player,
            $this->actionDef('broadside')
        );

        // Assert - the volley clears the board in one shot
        foreach ($defenders as $defender) {
            $this->assertEquals(0, $defender->refresh()->current_health, 'Broadside should one-shot a defender');
        }
        $this->varos->refresh();
        $this->assertEquals($varosHealthBefore, $this->varos->current_health, 'Varos stands behind his own volley');
        $this->assertStringContainsString('do not discriminate', $result['description']);
    }

    public function testBroadside_NoDefenders_OmitsDefenderMessage()
    {
        // Arrange
        $this->player->current_action = 'defend';

        // Act
        $result = $this->heroBattleService->processBroadsideAction(
            $this->freshVaros(),
            $this->player,
            $this->actionDef('broadside')
        );

        // Assert
        $this->assertStringNotContainsString('do not discriminate', $result['description']);
    }

    public function testBroadside_ClearedQuayAllowsRallyToRefill()
    {
        // Arrange - board is full, then the guns clear it
        $this->addDefenders(2);
        $this->player->current_action = 'defend';
        $this->heroBattleService->processBroadsideAction(
            $this->freshVaros(),
            $this->player,
            $this->actionDef('broadside')
        );
        $this->assertEquals(0, $this->countDefenders());

        // Act - Varos rallies again
        $this->heroBattleService->processRallyAction(
            $this->freshVaros(),
            $this->player,
            $this->actionDef('rally_the_defenders')
        );

        // Assert
        $this->assertEquals(1, $this->countDefenders());
    }

    // ------------------------------------------------------------------
    // Rally the Defenders
    // ------------------------------------------------------------------

    public function testRally_PlayerAttacks_SummonsNothing()
    {
        // Arrange
        $this->player->current_action = 'attack';

        // Act
        $result = $this->heroBattleService->processRallyAction(
            $this->freshVaros(),
            $this->player,
            $this->actionDef('rally_the_defenders')
        );

        // Assert
        $this->assertEquals(0, $this->countDefenders());
        $this->assertStringContainsString('silences him', $result['description']);
    }

    public function testRally_PlayerDefends_SummonsOneDefender()
    {
        // Arrange
        $this->player->current_action = 'defend';

        // Act
        $result = $this->heroBattleService->processRallyAction(
            $this->freshVaros(),
            $this->player,
            $this->actionDef('rally_the_defenders')
        );

        // Assert - one at a time, never a partial count
        $this->assertEquals(1, $this->countDefenders());
        $this->assertStringContainsString('a defender rushes to his aid', $result['description']);
    }

    public function testRally_RepeatedCalls_FillToCapOneAtATime()
    {
        // Arrange
        $this->player->current_action = 'defend';

        // Act - three calls against a cap of two
        for ($i = 0; $i < 3; $i++) {
            $this->heroBattleService->processRallyAction(
                $this->freshVaros(),
                $this->player,
                $this->actionDef('rally_the_defenders')
            );
        }

        // Assert
        $this->assertEquals(2, $this->countDefenders());
    }

    public function testRally_AtCap_SummonsNothing()
    {
        // Arrange
        $this->addDefenders(2);
        $this->player->current_action = 'defend';

        // Act
        $result = $this->heroBattleService->processRallyAction(
            $this->freshVaros(),
            $this->player,
            $this->actionDef('rally_the_defenders')
        );

        // Assert
        $this->assertEquals(2, $this->countDefenders());
        $this->assertStringContainsString('no more defenders answer', $result['description']);
    }

    public function testRally_DeadDefendersDoNotCountTowardCap()
    {
        // Arrange - board is full but all are dead
        $defenders = $this->addDefenders(2);
        foreach ($defenders as $defender) {
            $defender->update(['current_health' => 0]);
        }
        $this->player->current_action = 'defend';

        // Act
        $this->heroBattleService->processRallyAction(
            $this->freshVaros(),
            $this->player,
            $this->actionDef('rally_the_defenders')
        );

        // Assert - a fresh defender arrives
        $this->assertEquals(1, $this->countDefenders());
    }

    protected function countDefenders(): int
    {
        return HeroCombatant::where('hero_battle_id', $this->battle->id)
            ->whereNull('hero_id')
            ->where('id', '!=', $this->varos->id)
            ->where('current_health', '>', 0)
            ->count();
    }

    // ------------------------------------------------------------------
    // The Admiral's Challenge
    // ------------------------------------------------------------------

    public function testChallenge_PlayerFocuses_Negated()
    {
        // Arrange
        $this->player->current_action = 'focus';

        // Act
        $result = $this->heroBattleService->processChallengeAction(
            $this->freshVaros(),
            $this->player,
            $this->actionDef('admirals_challenge')
        );

        // Assert
        $this->assertEquals(0, $result['damage']);
        $this->assertStringContainsString('goes unanswered', $result['description']);
    }

    public function testChallenge_PlayerAttacks_TakesTheBait()
    {
        // Arrange
        $this->player->current_action = 'attack';

        // Act
        $result = $this->heroBattleService->processChallengeAction(
            $this->freshVaros(),
            $this->player,
            $this->actionDef('admirals_challenge')
        );

        // Assert
        $this->assertEquals(45, $result['damage']);
        $this->assertStringContainsString('takes the bait', $result['description']);
    }

    public function testChallenge_PlayerDefends_TakesDefaultDamage()
    {
        // Arrange
        $this->player->current_action = 'defend';

        // Act
        $result = $this->heroBattleService->processChallengeAction(
            $this->freshVaros(),
            $this->player,
            $this->actionDef('admirals_challenge')
        );

        // Assert
        $this->assertEquals(25, $result['damage']);
    }

    // ------------------------------------------------------------------
    // Telegraph cycle
    // ------------------------------------------------------------------

    public function testProcessStatus_OddTurn_TelegraphsAnOrder()
    {
        // Arrange
        $this->battle->update(['current_turn' => 1]);

        // Act
        $this->heroBattleService->processStatus($this->freshVaros());

        // Assert
        $this->varos->refresh();
        $this->assertContains(
            $this->varos->status['telegraphed_order'],
            ['broadside', 'rally_the_defenders', 'admirals_challenge']
        );
    }

    public function testDetermineAction_EvenTurn_FiresTelegraphedOrder()
    {
        // Arrange
        $this->battle->update(['current_turn' => 2]);
        $this->varos->update(['status' => ['telegraphed_order' => 'broadside']]);

        // Act
        $action = $this->heroBattleService->determineAction($this->freshVaros());

        // Assert - order fires and is cleared so it cannot repeat
        $this->assertEquals('broadside', $action['action']);
        $this->varos->refresh();
        $this->assertArrayNotHasKey('telegraphed_order', $this->varos->status ?? []);
    }

    public function testDetermineAction_OddTurn_DoesNotFireTelegraphedOrder()
    {
        // Arrange
        $this->battle->update(['current_turn' => 3]);
        $this->varos->update(['status' => ['telegraphed_order' => 'broadside']]);

        // Act
        $action = $this->heroBattleService->determineAction($this->freshVaros());

        // Assert - free turn, order stays queued for the even turn
        $this->assertNotEquals('broadside', $action['action']);
        $this->varos->refresh();
        $this->assertEquals('broadside', $this->varos->status['telegraphed_order']);
    }

    public function testProcessTurn_DrivesTelegraphThenFiresOrder()
    {
        // Arrange - queue several turns so processTurn chains through them
        $this->player->update(['actions' => [
            ['action' => 'defend', 'target' => null],
            ['action' => 'defend', 'target' => null],
            ['action' => 'defend', 'target' => null],
            ['action' => 'defend', 'target' => null],
        ]]);
        $varosHealthBefore = $this->varos->current_health;

        // Act - run the real turn loop
        $this->heroBattleService->processTurn($this->battle->fresh());

        // Assert - an order fired through the live loop
        $rows = HeroBattleAction::where('hero_battle_id', $this->battle->id)->get();
        $orders = ['broadside', 'rally_the_defenders', 'admirals_challenge'];
        $this->assertNotEmpty(
            array_intersect($rows->pluck('action')->all(), $orders),
            'Expected a telegraphed order to fire on an even turn'
        );

        // Assert - the player was warned first, in flavour text only
        $log = $rows->pluck('description')->implode(' ');
        $tells = ['Gunports slide open', 'turns toward his ship', 'crew begins to jeer'];
        $this->assertTrue(
            collect($tells)->contains(function ($tell) use ($log) { return str_contains($log, $tell); }),
            'Expected a telegraph tell in the combat log'
        );

        // Assert - Varos never damages himself with his own volley
        $this->varos->refresh();
        $this->assertLessThanOrEqual($varosHealthBefore, $this->varos->current_health);
        $this->assertGreaterThan(0, $this->varos->current_health);
    }

    public function testTelegraphTells_DoNotNameTheirCounterMove()
    {
        // Arrange - roll telegraphs across many odd turns to sample all three
        $log = '';
        for ($i = 0; $i < 30; $i++) {
            $this->battle->update(['current_turn' => 1]);
            $this->varos->update(['status' => null]);
            $this->heroBattleService->processStatus($this->freshVaros());
            $log .= HeroBattleAction::where('hero_battle_id', $this->battle->id)
                ->where('action', 'status')->pluck('description')->implode(' ');
        }

        // Assert - the tells stay pure flavour; the player must learn them
        foreach (['Defend', 'Attack', 'Focus', 'Counter', 'Recover'] as $actionName) {
            $this->assertStringNotContainsString($actionName, $log);
        }
    }

    public function testEncounter_AdmiralVarosIsRegistered()
    {
        // Assert - the key referenced by Round51RaidSeeder resolves
        $encounter = $this->heroEncounterHelper->getEncounters()->get('admiral_varos');
        $this->assertNotNull($encounter);
        $this->assertNotNull($this->heroEncounterHelper->getEnemies()->get($encounter['enemies'][0]['key']));
        $this->assertNotNull($this->heroEncounterHelper->getEnemies()->get('aurelis_defender'));
    }
}
