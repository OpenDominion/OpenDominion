<?php

namespace OpenDominion\Tests\Feature\HeroCombat;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hero;
use OpenDominion\Models\HeroBattle;
use OpenDominion\Models\HeroBattleAction;
use OpenDominion\Models\HeroCombatant;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\Actions\HeroActionService;
use OpenDominion\Services\Dominion\HeroBattleService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class HeroBattleFlowTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    protected Round $round;

    protected HeroBattleService $heroBattleService;

    protected HeroActionService $heroActionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->round = $this->createRound('-3 days midnight');
        $this->heroBattleService = $this->app->make(HeroBattleService::class);
        $this->heroActionService = $this->app->make(HeroActionService::class);
    }

    protected function dominionWithHero(string $heroName, string $class = 'blacksmith'): Dominion
    {
        $user = $this->createUser();
        $dominion = $this->createDominionWithLegacyStats($user, $this->round, Race::where('name', 'Human')->firstOrFail());

        Hero::create([
            'dominion_id' => $dominion->id,
            'name' => $heroName,
            'class' => $class,
            'experience' => 2000,
            'class_data' => [],
        ]);

        return $dominion->refresh();
    }

    protected function combatantFor(HeroBattle $battle, Dominion $dominion): HeroCombatant
    {
        return $battle->combatants()->where('dominion_id', $dominion->id)->firstOrFail();
    }

    public function testPracticeBattleCreatesTeamsAndPersistsEngineState(): void
    {
        $dominion = $this->dominionWithHero('Solo');

        $battle = $this->heroBattleService->createPracticeBattle($dominion);

        $this->assertEquals('practice', $battle->mode);
        $this->assertEquals('default', $battle->encounter_key);
        $this->assertGreaterThan(0, $battle->seed);
        $this->assertCount(2, $battle->combatants);

        $player = $this->combatantFor($battle, $dominion);
        $twin = $battle->combatants()->whereNull('hero_id')->firstOrFail();
        $this->assertEquals(1, $player->team);
        $this->assertEquals(2, $twin->team);
        $this->assertEquals('evil_twin', $twin->template_key);
        $this->assertEquals($player->health, $twin->health, 'The evil twin copies your stats');
        $this->assertEquals(['attack', 'defend', 'focus', 'counter', 'recover'], $player->abilities);
    }

    public function testQueuedActionResolvesAndEffectsRoundTripThroughDatabase(): void
    {
        $dominion = $this->dominionWithHero('Solo');
        $battle = $this->heroBattleService->createPracticeBattle($dominion);
        $player = $this->combatantFor($battle, $dominion);

        $this->heroActionService->queueAction($dominion, $player, null, 'focus');
        $processed = $this->heroBattleService->processTurn($battle->fresh());

        $this->assertTrue($processed);
        $battle->refresh();
        $player->refresh();
        $this->assertEquals(2, $battle->current_turn);
        $this->assertEquals('focus', $player->last_action);
        $this->assertEquals('focused', $player->effects[0]['key']);
        $this->assertEquals(3, $player->cooldowns['focus']);
        $this->assertSame([], $player->actions);

        $rows = HeroBattleAction::where('hero_battle_id', $battle->id)->get();
        $this->assertTrue($rows->contains('action', 'focus'));
        $this->assertStringContainsString('focuses their energy', $rows->pluck('description')->implode(' '));

        $view = $this->heroBattleService->loadBattle($battle);
        $this->assertTrue($view->effects->has($view->combatant($player->id), 'focused'));
    }

    public function testQueueRejectsActionOnCooldown(): void
    {
        $dominion = $this->dominionWithHero('Solo');
        $battle = $this->heroBattleService->createPracticeBattle($dominion);
        $player = $this->combatantFor($battle, $dominion);

        $this->heroActionService->queueAction($dominion, $player, null, 'counter');

        $this->expectException(\OpenDominion\Exceptions\GameException::class);
        $this->heroActionService->queueAction($dominion, $player->refresh(), null, 'counter');
    }

    public function testQueueRejectsHostileActionAgainstOwnSide(): void
    {
        $alice = $this->dominionWithHero('Alice');
        $bob = $this->dominionWithHero('Bob');
        $battle = $this->heroBattleService->createEncounterBattle('default', [$alice, $bob], 'practice');

        $this->expectException(\OpenDominion\Exceptions\GameException::class);
        $this->heroActionService->queueAction(
            $alice,
            $this->combatantFor($battle, $alice),
            $this->combatantFor($battle, $bob),
            'attack',
        );
    }

    public function testCoopBattleWaitsForEveryPlayer(): void
    {
        $alice = $this->dominionWithHero('Alice');
        $bob = $this->dominionWithHero('Bob');
        $battle = $this->heroBattleService->createEncounterBattle('default', [$alice, $bob], 'practice');
        $twin = $battle->combatants()->whereNull('hero_id')->firstOrFail();

        $this->heroActionService->queueAction($alice, $this->combatantFor($battle, $alice), $twin, 'attack');
        $this->assertFalse($this->heroBattleService->processTurn($battle->fresh()));

        $this->heroActionService->queueAction($bob, $this->combatantFor($battle, $bob), $twin, 'attack');
        $this->assertTrue($this->heroBattleService->processTurn($battle->fresh()));
        $this->assertEquals(2, $battle->fresh()->current_turn);
    }

    public function testAutomatedPvpBattleFinishesAndRecordsResults(): void
    {
        $alice = $this->dominionWithHero('Alice');
        $bob = $this->dominionWithHero('Bob');
        $battle = $this->heroBattleService->createBattle($alice, $bob);
        $battle->combatants()->update(['automated' => true]);

        $this->heroBattleService->processTurn($battle->fresh());

        $battle->refresh();
        $this->assertTrue($battle->finished);
        $this->assertTrue($battle->pvp);

        $aliceHero = $alice->hero->fresh();
        $bobHero = $bob->hero->fresh();
        $totalGames = $aliceHero->stat_combat_wins + $aliceHero->stat_combat_losses + $aliceHero->stat_combat_draws;
        $this->assertEquals(1, $totalGames);

        if ($battle->isDraw()) {
            $this->assertEquals(1, $bobHero->stat_combat_draws);
        } else {
            $this->assertNotNull($battle->winner_combatant_id);
            $this->assertEquals($battle->winning_team, $battle->winner->team);
            $this->assertEquals(1, $aliceHero->stat_combat_wins + $bobHero->stat_combat_wins);
            $this->assertNotEquals($aliceHero->combat_rating, $bobHero->combat_rating);
        }
    }

    public function testTeamPvpSharesTheWin(): void
    {
        $a1 = $this->dominionWithHero('A1');
        $a2 = $this->dominionWithHero('A2');
        $b1 = $this->dominionWithHero('B1');
        $battle = $this->heroBattleService->createTeamBattle([[$a1, $a2], [$b1]]);
        $battle->combatants()->update(['automated' => true]);

        $this->heroBattleService->processTurn($battle->fresh());
        $battle->refresh();

        $this->assertTrue($battle->finished);
        if ($battle->winning_team === 1) {
            $this->assertEquals(1, $a1->hero->fresh()->stat_combat_wins);
            $this->assertEquals(1, $a2->hero->fresh()->stat_combat_wins);
            $this->assertEquals(1, $b1->hero->fresh()->stat_combat_losses);
            $this->assertEquals('A1 & A2', $battle->winnerLabel());
        } else {
            $this->assertEquals(1, $a1->hero->fresh()->stat_combat_losses + $a1->hero->fresh()->stat_combat_draws);
        }
    }

    public function testTimeBankExpirySwitchesPlayerToAutomated(): void
    {
        $dominion = $this->dominionWithHero('Solo');
        $battle = $this->heroBattleService->createPracticeBattle($dominion);
        $player = $this->combatantFor($battle, $dominion);
        $player->update(['time_bank' => 10]);
        $battle->update(['last_processed_at' => now()->subMinutes(5)]);

        $this->heroBattleService->checkTime($battle->fresh());
        $this->assertTrue($player->fresh()->automated);

        $this->assertTrue($this->heroBattleService->processTurn($battle->fresh()));
    }

    public function testStoredBattleReplaysIdentically(): void
    {
        $dominion = $this->dominionWithHero('Solo');
        $battle = $this->heroBattleService->createPracticeBattle($dominion, 'admiral_varos');
        $player = $this->combatantFor($battle, $dominion);
        $varos = $battle->combatants()->where('template_key', 'admiral_varos')->firstOrFail();

        foreach (['defend', 'focus', 'attack', 'counter'] as $ability) {
            $player->refresh();
            $target = $ability === 'attack' ? $varos : null;
            $this->heroActionService->queueAction($dominion, $player, $target, $ability);
        }
        $this->heroBattleService->processTurn($battle->fresh());

        $battle->combatants()->whereNotNull('hero_id')->update(['automated' => true]);
        $this->heroBattleService->processTurn($battle->fresh());
        $battle->refresh();
        $this->assertTrue($battle->finished);

        $result = app(\OpenDominion\HeroCombat\Persistence\BattleReplayer::class)->replay($battle);

        $this->assertGreaterThan(4, $result['turns']);
        $this->assertSame([], $result['mismatches']);
        $this->assertEquals($battle->winning_team, $result['battle']->state->winningTeam);

        \Illuminate\Support\Facades\Artisan::call('hero-combat:replay', ['battle' => $battle->id]);
        $this->assertStringContainsString('matches the stored combat log', \Illuminate\Support\Facades\Artisan::output());
    }

    public function testUnknownEncounterIsRejected(): void
    {
        $dominion = $this->dominionWithHero('Solo');

        $this->expectException(\OpenDominion\Exceptions\GameException::class);
        $this->heroBattleService->createPracticeBattle($dominion, 'does_not_exist');
    }
}
