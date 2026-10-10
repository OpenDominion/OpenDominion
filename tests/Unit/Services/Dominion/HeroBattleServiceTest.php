<?php

namespace OpenDominion\Tests\Unit\Services\Dominion;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\HeroCombat\Engine\ActionValidator;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\Effects\Hook;
use OpenDominion\HeroCombat\Engine\IntentDecider;
use OpenDominion\HeroCombat\Engine\Random\SeededRandomSource;
use OpenDominion\HeroCombat\Registry\CombatRegistry;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hero;
use OpenDominion\Models\HeroBattleAction;
use OpenDominion\Models\Race;
use OpenDominion\Services\Dominion\HeroBattleService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;
use OpenDominion\Tests\Unit\HeroCombat\Support\BattleBuilder;
use OpenDominion\Tests\Unit\HeroCombat\Support\BuildsBattles;

/**
 * Admiral Varos: telegraphed orders, reactive outcomes and a capped summon.
 */
class HeroBattleServiceTest extends AbstractBrowserKitTestCase
{
    use BuildsBattles;
    use DatabaseTransactions;

    protected Battle $battle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->battle = BattleBuilder::make('admiral_varos')
            ->withRegistry($this->app->make(CombatRegistry::class))
            ->withRoster()
            ->hero('Player')
            ->build();
    }

    protected function addDefenders(int $count): array
    {
        $defenders = [];
        for ($i = 0; $i < $count; $i++) {
            $defenders[] = $this->battle->summon('aurelis_defender', 2, 'Aurelis Defender #' . ($i + 1));
        }

        return $defenders;
    }

    protected function countDefenders(): int
    {
        return count($this->battle->withTemplate('aurelis_defender'));
    }

    protected function varos(): \OpenDominion\HeroCombat\Engine\CombatantState
    {
        return $this->named($this->battle, 'Admiral Varos');
    }

    protected function playerHealthLost(callable $action): int
    {
        $player = $this->named($this->battle, 'Player');
        $before = $player->currentHealth;
        $action();

        return $before - $player->currentHealth;
    }

    // ------------------------------------------------------------------
    // Broadside
    // ------------------------------------------------------------------

    public function testBroadside_PlayerDefends_TakesReducedDamage()
    {
        $this->declare($this->battle, 'Player', 'defend');

        $text = '';
        $damage = $this->playerHealthLost(function () use (&$text) {
            $text = $this->perform($this->battle, 'Admiral Varos', 'broadside', 'Player');
        });

        $this->assertEquals(15, $damage);
        $this->assertStringContainsString('drops behind the stonework', $text);
    }

    public function testBroadside_PlayerCounters_TakesFullVolley()
    {
        // Counter is the trap: strong against blade_flurry, worst here
        $this->declare($this->battle, 'Player', 'counter');

        $text = '';
        $damage = $this->playerHealthLost(function () use (&$text) {
            $text = $this->perform($this->battle, 'Admiral Varos', 'broadside', 'Player');
        });

        $this->assertEquals(45, $damage);
        $this->assertStringContainsString('a blade that never comes', $text);
    }

    public function testBroadside_PlayerAttacks_TakesDefaultDamage()
    {
        $this->declare($this->battle, 'Player', 'attack');

        $damage = $this->playerHealthLost(function () {
            $this->perform($this->battle, 'Admiral Varos', 'broadside', 'Player');
        });

        $this->assertEquals(30, $damage);
    }

    public function testBroadside_KillsEveryDefenderButNotVaros()
    {
        $defenders = $this->addDefenders(2);
        $this->declare($this->battle, 'Player', 'defend');
        $varosHealthBefore = $this->varos()->currentHealth;

        $text = $this->perform($this->battle, 'Admiral Varos', 'broadside', 'Player');

        foreach ($defenders as $defender) {
            $this->assertEquals(0, $defender->currentHealth, 'Broadside should one-shot a defender');
        }
        $this->assertEquals($varosHealthBefore, $this->varos()->currentHealth, 'Varos stands behind his own volley');
        $this->assertStringContainsString('do not discriminate', $text);
    }

    public function testBroadside_NoDefenders_OmitsDefenderMessage()
    {
        $this->declare($this->battle, 'Player', 'defend');

        $text = $this->perform($this->battle, 'Admiral Varos', 'broadside', 'Player');

        $this->assertStringNotContainsString('do not discriminate', $text);
    }

    public function testBroadside_ClearedQuayAllowsRallyToRefill()
    {
        $this->addDefenders(2);
        $this->declare($this->battle, 'Player', 'defend');
        $this->perform($this->battle, 'Admiral Varos', 'broadside', 'Player');
        $this->assertEquals(0, $this->countDefenders());

        $this->perform($this->battle, 'Admiral Varos', 'rally_the_defenders', 'Player');

        $this->assertEquals(1, $this->countDefenders());
    }

    // ------------------------------------------------------------------
    // Rally the Defenders
    // ------------------------------------------------------------------

    public function testRally_PlayerAttacks_SummonsNothing()
    {
        $this->declare($this->battle, 'Player', 'attack');

        $text = $this->perform($this->battle, 'Admiral Varos', 'rally_the_defenders', 'Player');

        $this->assertEquals(0, $this->countDefenders());
        $this->assertStringContainsString('silences him', $text);
    }

    public function testRally_PlayerDefends_SummonsOneDefender()
    {
        $this->declare($this->battle, 'Player', 'defend');

        $text = $this->perform($this->battle, 'Admiral Varos', 'rally_the_defenders', 'Player');

        $this->assertEquals(1, $this->countDefenders());
        $this->assertStringContainsString('a defender rushes to his aid', $text);
        $this->assertEquals(2, $this->battle->withTemplate('aurelis_defender')[0]->team, 'Summons join the summoner\'s team');
    }

    public function testRally_RepeatedCalls_FillToCapOneAtATime()
    {
        $this->declare($this->battle, 'Player', 'defend');

        for ($i = 0; $i < 3; $i++) {
            $this->perform($this->battle, 'Admiral Varos', 'rally_the_defenders', 'Player');
        }

        $this->assertEquals(2, $this->countDefenders());
    }

    public function testRally_AtCap_SummonsNothing()
    {
        $this->addDefenders(2);
        $this->declare($this->battle, 'Player', 'defend');

        $text = $this->perform($this->battle, 'Admiral Varos', 'rally_the_defenders', 'Player');

        $this->assertEquals(2, $this->countDefenders());
        $this->assertStringContainsString('no more defenders answer', $text);
    }

    public function testRally_DeadDefendersDoNotCountTowardCap()
    {
        foreach ($this->addDefenders(2) as $defender) {
            $defender->currentHealth = 0;
        }
        $this->declare($this->battle, 'Player', 'defend');

        $this->perform($this->battle, 'Admiral Varos', 'rally_the_defenders', 'Player');

        $this->assertEquals(1, $this->countDefenders());
    }

    // ------------------------------------------------------------------
    // The Admiral's Challenge
    // ------------------------------------------------------------------

    public function testChallenge_PlayerFocuses_Negated()
    {
        $this->declare($this->battle, 'Player', 'focus');

        $text = '';
        $damage = $this->playerHealthLost(function () use (&$text) {
            $text = $this->perform($this->battle, 'Admiral Varos', 'admirals_challenge', 'Player');
        });

        $this->assertEquals(0, $damage);
        $this->assertStringContainsString('goes unanswered', $text);
    }

    public function testChallenge_PlayerAttacks_TakesTheBait()
    {
        $this->declare($this->battle, 'Player', 'attack');

        $text = '';
        $damage = $this->playerHealthLost(function () use (&$text) {
            $text = $this->perform($this->battle, 'Admiral Varos', 'admirals_challenge', 'Player');
        });

        $this->assertEquals(45, $damage);
        $this->assertStringContainsString('takes the bait', $text);
    }

    public function testChallenge_PlayerDefends_TakesDefaultDamage()
    {
        $this->declare($this->battle, 'Player', 'defend');

        $damage = $this->playerHealthLost(function () {
            $this->perform($this->battle, 'Admiral Varos', 'admirals_challenge', 'Player');
        });

        $this->assertEquals(25, $damage);
    }

    // ------------------------------------------------------------------
    // Telegraph cycle
    // ------------------------------------------------------------------

    protected function orders(): \OpenDominion\HeroCombat\Engine\Effects\EffectInstance
    {
        return $this->battle->effects->find($this->varos(), 'admirals_orders');
    }

    protected function decideVaros(): string
    {
        return (new IntentDecider($this->battle, new ActionValidator($this->battle)))->decide($this->varos())->abilityKey;
    }

    public function testTelegraph_OddTurnEnd_TelegraphsAnOrder()
    {
        $this->battle->state->turn = 1;

        $this->battle->dispatcher->runEverywhere(Hook::TurnEnd, $this->battle);

        $this->assertContains(
            $this->orders()->data['pending'],
            ['broadside', 'rally_the_defenders', 'admirals_challenge']
        );
    }

    public function testDetermineAction_EvenTurn_FiresTelegraphedOrder()
    {
        $this->battle->state->turn = 2;
        $this->orders()->data['pending'] = 'broadside';

        $action = $this->decideVaros();

        // Order fires and is cleared so it cannot repeat
        $this->assertEquals('broadside', $action);
        $this->assertArrayNotHasKey('pending', $this->orders()->data);
    }

    public function testDetermineAction_OddTurn_DoesNotFireTelegraphedOrder()
    {
        $this->battle->state->turn = 3;
        $this->orders()->data['pending'] = 'broadside';

        $action = $this->decideVaros();

        // Free turn, order stays queued for the even turn
        $this->assertNotEquals('broadside', $action);
        $this->assertEquals('broadside', $this->orders()->data['pending']);
    }

    public function testProcessTurn_DrivesTelegraphThenFiresOrder()
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('-3 days midnight');
        $dominion = $this->createDominionWithLegacyStats($user, $round, Race::where('name', 'Human')->firstOrFail());
        Hero::create([
            'dominion_id' => $dominion->id,
            'name' => 'Test Hero',
            'class' => 'blacksmith',
            'experience' => 2000,
            'class_data' => [],
        ]);
        $dominion->refresh();

        $heroBattleService = $this->app->make(HeroBattleService::class);
        $heroBattle = $heroBattleService->createPracticeBattle($dominion, 'admiral_varos');
        $player = $heroBattle->combatants()->where('dominion_id', $dominion->id)->firstOrFail();
        $varos = $heroBattle->combatants()->where('template_key', 'admiral_varos')->firstOrFail();
        $player->update(['actions' => array_fill(0, 4, ['ability' => 'defend', 'target' => null])]);
        $varosHealthBefore = $varos->current_health;

        $heroBattleService->processTurn($heroBattle->fresh());

        // An order fired through the live loop
        $rows = HeroBattleAction::where('hero_battle_id', $heroBattle->id)->get();
        $orders = ['broadside', 'rally_the_defenders', 'admirals_challenge'];
        $this->assertNotEmpty(
            array_intersect($rows->pluck('action')->all(), $orders),
            'Expected a telegraphed order to fire on an even turn'
        );

        // The player was warned first, in flavour text only
        $log = $rows->pluck('description')->implode(' ');
        $tells = ['Gunports slide open', 'turns toward his ship', 'crew begins to jeer'];
        $this->assertTrue(
            collect($tells)->contains(fn ($tell) => str_contains($log, $tell)),
            'Expected a telegraph tell in the combat log'
        );

        // Varos never damages himself with his own volley
        $varos->refresh();
        $this->assertLessThanOrEqual($varosHealthBefore, $varos->current_health);
        $this->assertGreaterThan(0, $varos->current_health);
    }

    public function testTelegraphTells_DoNotNameTheirCounterMove()
    {
        // Roll telegraphs across many odd turns to sample all three
        $log = '';
        for ($i = 0; $i < 30; $i++) {
            $this->battle->state->turn = 1;
            $this->battle->random = new SeededRandomSource($i);
            unset($this->orders()->data['pending']);
            $this->battle->dispatcher->runEverywhere(Hook::TurnEnd, $this->battle);
            $log .= ' ' . $this->battle->log->text();
            $this->battle->log->flush();
        }

        // The tells stay pure flavour; the player must learn them
        foreach (['Defend', 'Attack', 'Focus', 'Counter', 'Recover'] as $actionName) {
            $this->assertStringNotContainsString($actionName, $log);
        }
    }

    public function testEncounter_AdmiralVarosIsRegistered()
    {
        // The key referenced by Round51RaidSeeder resolves
        $registry = $this->app->make(CombatRegistry::class);
        $encounter = $registry->encounter('admiral_varos');
        $roster = $encounter->roster(new \OpenDominion\HeroCombat\Engine\EncounterContext());

        $this->assertNotNull($registry->enemy($roster[0]['template']));
        $this->assertNotNull($registry->enemy('aurelis_defender'));
    }
}
