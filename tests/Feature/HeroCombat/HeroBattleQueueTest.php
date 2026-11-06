<?php

namespace OpenDominion\Tests\Feature\HeroCombat;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\HeroCombat\Persistence\CombatantFactory;
use OpenDominion\HeroCombat\Presentation\BattlePresenter;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hero;
use OpenDominion\Models\HeroBattle;
use OpenDominion\Models\HeroBattleQueue;
use OpenDominion\Models\HeroCombatant;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\Actions\HeroActionService;
use OpenDominion\Services\Dominion\HeroBattleService;
use OpenDominion\Tests\AbstractTestCase;

/**
 * The PvP queue: 1v1, or 2v2 where each hero brings an identical templated ally its
 * dominion pilots.
 */
class HeroBattleQueueTest extends AbstractTestCase
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

    protected function dominionWithHero(float $experience = 2000, ?Round $round = null, bool $impersonate = false): Dominion
    {
        $user = $impersonate ? $this->createAndImpersonateUser() : $this->createUser();
        $dominion = $this->createDominionWithLegacyStats($user, $round ?? $this->round, Race::where('name', 'Human')->firstOrFail());

        Hero::create([
            'dominion_id' => $dominion->id,
            'name' => 'Queue Hero ' . $dominion->id,
            'class' => 'blacksmith',
            'experience' => $experience,
            'class_data' => [],
        ]);

        return $dominion->refresh();
    }

    protected function twoVersusTwo(float $aliceExperience = 2000, float $bobExperience = 2000): array
    {
        $alice = $this->dominionWithHero($aliceExperience);
        $bob = $this->dominionWithHero($bobExperience);

        $this->assertNull($this->heroBattleService->joinQueue($alice, 2));
        $battle = $this->heroBattleService->joinQueue($bob, 2);
        $this->assertNotNull($battle);

        return [$alice, $bob, $battle->fresh()];
    }

    /**
     * @return array{hero: HeroCombatant, ally: HeroCombatant}
     */
    protected function sideOf(HeroBattle $battle, Dominion $dominion): array
    {
        $controlled = $battle->combatants()->where('controller_dominion_id', $dominion->id)->get();

        return [
            'hero' => $controlled->whereNotNull('hero_id')->sole(),
            'ally' => $controlled->whereNull('hero_id')->sole(),
        ];
    }

    public function testOneVersusOneMatchesTheWaitingHero(): void
    {
        $alice = $this->dominionWithHero();
        $bob = $this->dominionWithHero();

        $this->assertNull($this->heroBattleService->joinQueue($alice, 1));
        $battle = $this->heroBattleService->joinQueue($bob, 1);

        $this->assertNotNull($battle);
        $this->assertCount(2, $battle->combatants);
        $this->assertCount(2, $battle->combatants->whereNotNull('hero_id'));
        $this->assertFalse(HeroBattleQueue::whereIn('hero_id', [$alice->hero->id, $bob->hero->id])->exists());
    }

    public function testQueuesOnlyMatchTheSameSize(): void
    {
        $alice = $this->dominionWithHero();
        $bob = $this->dominionWithHero();

        $this->assertNull($this->heroBattleService->joinQueue($alice, 1));
        $this->assertNull($this->heroBattleService->joinQueue($bob, 2));

        $this->assertFalse(HeroBattle::query()->controlledBy($alice)->exists());
        $this->assertEqualsCanonicalizing([1, 2], HeroBattleQueue::whereIn('hero_id', [$alice->hero->id, $bob->hero->id])->pluck('size')->all());
    }

    public function testRejectsUnknownSizes(): void
    {
        $this->expectException(GameException::class);
        $this->heroBattleService->joinQueue($this->dominionWithHero(), 3);
    }

    public function testAHeroCanWaitInOnlyOneQueue(): void
    {
        $alice = $this->dominionWithHero();
        $this->heroBattleService->joinQueue($alice, 1);

        $this->expectException(GameException::class);
        $this->heroBattleService->joinQueue($alice->refresh(), 2);
    }

    public function testHeroesFromAnotherRoundAreNeverMatched(): void
    {
        $stranger = $this->dominionWithHero(2000, $this->createRound('-3 days midnight'));
        $this->heroBattleService->joinQueue($stranger, 1);

        $this->assertNull($this->heroBattleService->joinQueue($this->dominionWithHero(), 1));
        $this->assertFalse(HeroBattle::query()->controlledBy($stranger)->exists());
    }

    public function testExpiredEntriesAreNeverMatched(): void
    {
        $alice = $this->dominionWithHero();
        $this->heroBattleService->joinQueue($alice, 1);
        HeroBattleQueue::where('hero_id', $alice->hero->id)->update(['created_at' => now()->subHours(HeroBattleService::QUEUE_EXPIRY_HOURS)->subMinute()]);

        $this->assertNull($this->heroBattleService->joinQueue($this->dominionWithHero(), 1));
        $this->assertFalse(HeroBattle::query()->controlledBy($alice)->exists());
    }

    public function testTwoVersusTwoGivesEachHeroAnIdenticalAllyAtTheLowerLevel(): void
    {
        [$alice, $bob, $battle] = $this->twoVersusTwo(1200, 3500);

        $this->assertCount(4, $battle->combatants);
        $aliceSide = $this->sideOf($battle, $alice);
        $bobSide = $this->sideOf($battle, $bob);

        $this->assertSame($aliceSide['hero']->team, $aliceSide['ally']->team);
        $this->assertSame($bobSide['hero']->team, $bobSide['ally']->team);
        $this->assertNotSame($aliceSide['hero']->team, $bobSide['hero']->team);

        $stats = ['health', 'attack', 'defense', 'evasion', 'focus', 'counter', 'recover'];
        $baseAtLevelThree = app(HeroCalculator::class)->getBaseCombatStats(3);
        foreach ([$aliceSide['ally'], $bobSide['ally']] as $ally) {
            $this->assertSame(CombatantFactory::ALLY_TEMPLATE, $ally->template_key);
            $this->assertNull($ally->dominion_id, 'Owned by no one');
            $this->assertEquals($baseAtLevelThree, $ally->only($stats), 'Base stats at the lower hero level');
            $this->assertFalse((bool) $ally->automated);
        }
    }

    public function testThePlayerLocksInTheHeroAndAllyTogether(): void
    {
        [$alice, $bob, $battle] = $this->twoVersusTwo();
        $aliceSide = $this->sideOf($battle, $alice);
        $bobSide = $this->sideOf($battle, $bob);

        try {
            $this->heroActionService->queueOrders($alice, $battle, [$aliceSide['hero']->id => ['action' => 'attack', 'target' => null]]);
            $this->fail('The ally needs orders too');
        } catch (GameException) {
            $this->assertEmpty($aliceSide['hero']->fresh()->actions);
        }

        try {
            $this->heroActionService->queueOrders($alice, $battle, [$bobSide['ally']->id => ['action' => 'attack', 'target' => null]]);
            $this->fail('Only your own side');
        } catch (GameException) {
            $this->assertEmpty($bobSide['ally']->fresh()->actions);
        }

        $this->heroActionService->queueOrders($alice, $battle, [
            $aliceSide['hero']->id => ['action' => 'attack', 'target' => $bobSide['hero']->id],
            $aliceSide['ally']->id => ['action' => 'defend', 'target' => $aliceSide['hero']->id],
        ]);
        $this->assertFalse($this->heroBattleService->processTurn($battle->fresh()), 'Waits for the other player');

        $this->heroActionService->queueOrders($bob, $battle->fresh(), [
            $bobSide['hero']->id => ['action' => 'attack', 'target' => $aliceSide['hero']->id],
            $bobSide['ally']->id => ['action' => 'attack', 'target' => $aliceSide['hero']->id],
        ]);
        $this->assertTrue($this->heroBattleService->processTurn($battle->fresh()));
        $this->assertSame(2, $battle->fresh()->current_turn);
    }

    public function testRunningOutOfTimeAutomatesTheHeroAndAlly(): void
    {
        [$alice, , $battle] = $this->twoVersusTwo();
        $battle->update(['last_processed_at' => now()->subSeconds(CombatantFactory::DEFAULT_TIME_BANK + 60)]);

        $this->heroBattleService->checkTime($battle->fresh());

        $aliceSide = $this->sideOf($battle, $alice);
        $this->assertTrue((bool) $aliceSide['hero']->automated);
        $this->assertTrue((bool) $aliceSide['ally']->automated);
    }

    public function testAlliesEarnNothingAndHeroesAreRatedAgainstEachOther(): void
    {
        [$alice, $bob, $battle] = $this->twoVersusTwo();
        $battle->combatants()->update(['automated' => true]);

        $this->heroBattleService->processTurn($battle->fresh());
        $battle = $battle->fresh();

        $this->assertTrue($battle->finished);
        $heroCalculator = app(HeroCalculator::class);
        foreach ([$alice, $bob] as $dominion) {
            $hero = $this->sideOf($battle, $dominion)['hero'];
            $score = $battle->isDraw() ? 0.5 : ($battle->isWinner($hero) ? 1 : 0);
            $this->assertEquals($heroCalculator->calculateRatingChange(1000, 1000, $score), $dominion->hero->fresh()->combat_rating, 'Rated against the opposing hero only');
        }
        if (!$battle->isDraw()) {
            $this->assertNotNull($battle->winner->hero_id, 'A hero represents the winning side');
        }
    }

    public function testWaitingIsReportedPerQueueWithoutCountingYourself(): void
    {
        $alice = $this->dominionWithHero();
        $bob = $this->dominionWithHero();
        $this->heroBattleService->joinQueue($alice, 2);

        $this->assertSame([1 => false, 2 => true], $this->heroBattleService->queueWaiting($bob));
        $this->assertSame([1 => false, 2 => false], $this->heroBattleService->queueWaiting($alice));
        $this->assertSame(2, $this->heroBattleService->battleStatus($alice)['queued']);
        $this->assertNull($this->heroBattleService->battleStatus($bob)['queued']);
    }

    public function testStatusTracksYourActiveBattlesTurns(): void
    {
        [$alice, , $battle] = $this->twoVersusTwo();
        $this->dominionWithHero();

        $this->assertSame([$battle->id => ['turn' => 1, 'finished' => false]], $this->heroBattleService->battleStatus($alice)['battles']);
        $this->assertSame([], $this->heroBattleService->battleStatus($this->dominionWithHero())['battles']);
    }

    public function testStatusEndpointReturnsTheSelectedDominionsStatus(): void
    {
        $alice = $this->dominionWithHero(2000, impersonate: true);
        $this->selectDominion($alice);
        $this->heroBattleService->joinQueue($this->dominionWithHero(), 1);

        $this->getJson(route('api.dominion.hero-battles.status'))
            ->assertOk()
            ->assertExactJson(['queued' => null, 'waiting' => ['1' => true, '2' => false], 'battles' => []]);
    }

    public function testBattlesPageOffersBothQueuesAndShowsWhoIsWaiting(): void
    {
        $alice = $this->dominionWithHero(2000, impersonate: true);
        $this->selectDominion($alice);
        $this->heroBattleService->joinQueue($this->dominionWithHero(), 2);

        $content = $this->get(route('dominion.heroes.battles'))->assertOk()->getContent();

        $this->assertStringContainsString('data-queue-size="1"', $content);
        $this->assertStringContainsString('data-queue-size="2"', $content);
        $this->assertSame(1, substr_count($content, 'data-queue-waiting'));
        $this->assertStringNotContainsString(str_replace('/', '\\/', route('api.dominion.hero-battles.status')), $content, 'Idle: nothing to poll for');
    }

    public function testAwaitingOrdersUntilEveryCombatantYouPilotHasActed(): void
    {
        [$alice, $bob, $battle] = $this->twoVersusTwo();
        $aliceSide = $this->sideOf($battle, $alice);
        $bobSide = $this->sideOf($battle, $bob);

        $this->assertTrue($this->heroBattleService->isAwaitingOrders($alice));

        $this->heroActionService->queueOrders($alice, $battle, [
            $aliceSide['hero']->id => ['action' => 'attack', 'target' => $bobSide['hero']->id],
            $aliceSide['ally']->id => ['action' => 'attack', 'target' => $bobSide['hero']->id],
        ]);
        $this->assertFalse($this->heroBattleService->isAwaitingOrders($alice), 'Locked in');
        $this->assertTrue($this->heroBattleService->isAwaitingOrders($bob));

        $battle->combatants()->where('controller_dominion_id', $bob->id)->update(['automated' => true]);
        $this->assertFalse($this->heroBattleService->isAwaitingOrders($bob), 'On autopilot');
    }

    public function testTheBattleSaysWhenItIsWaitingOnYou(): void
    {
        [$alice, $bob, $battle] = $this->twoVersusTwo();
        $presenter = app(BattlePresenter::class);
        $aliceSide = $this->sideOf($battle, $alice);
        $bobSide = $this->sideOf($battle, $bob);

        $this->assertTrue($presenter->present($battle, $alice->id)->isWaitingOnYou());

        $this->heroActionService->queueOrders($alice, $battle, [
            $aliceSide['hero']->id => ['action' => 'attack', 'target' => $bobSide['hero']->id],
            $aliceSide['ally']->id => ['action' => 'attack', 'target' => $bobSide['hero']->id],
        ]);

        $aliceView = $presenter->present($battle->fresh(), $alice->id);
        $this->assertFalse($aliceView->isWaitingOnYou(), 'Locked in');
        $this->assertCount(2, $aliceView->waitingOn(), 'Now waiting on the opponent and their ally');
        $this->assertTrue($presenter->present($battle->fresh(), $bob->id)->isWaitingOnYou());
        $this->assertFalse($presenter->present($battle->fresh())->isWaitingOnYou(), 'A spectator is never waited on');
    }

    public function testBattlesPageOnlyPollsWhileWaitingOnSomeoneElse(): void
    {
        $alice = $this->dominionWithHero(2000, impersonate: true);
        $bob = $this->dominionWithHero();
        $this->selectDominion($alice);
        $pollUrl = str_replace('/', '\\/', route('api.dominion.hero-battles.status'));

        $this->heroBattleService->joinQueue($bob, 2);
        $battle = $this->heroBattleService->joinQueue($alice, 2);

        $content = $this->get(route('dominion.heroes.battles'))->assertOk()->getContent();
        $this->assertStringNotContainsString($pollUrl, $content, 'Your move: nothing to poll for');

        $aliceSide = $this->sideOf($battle, $alice);
        $bobSide = $this->sideOf($battle, $bob);
        $this->heroActionService->queueOrders($alice, $battle->fresh(), [
            $aliceSide['hero']->id => ['action' => 'attack', 'target' => $bobSide['hero']->id],
            $aliceSide['ally']->id => ['action' => 'attack', 'target' => $bobSide['hero']->id],
        ]);

        $content = $this->get(route('dominion.heroes.battles'))->assertOk()->getContent();
        $this->assertStringContainsString($pollUrl, $content, 'Waiting on the opponent');

        $battle->combatants()->update(['automated' => true]);
        $this->heroBattleService->processTurn($battle->fresh());
        $this->assertTrue($battle->fresh()->finished);

        $content = $this->get(route('dominion.heroes.battles'))->assertOk()->getContent();
        $this->assertStringNotContainsString($pollUrl, $content, 'The battle is over');
    }

    public function testJoiningTheTwoVersusTwoQueueThroughTheRoute(): void
    {
        $alice = $this->dominionWithHero(2000, impersonate: true);
        $this->selectDominion($alice);

        $this->get(route('dominion.heroes.battles.queue', ['size' => 2]))->assertRedirect(route('dominion.heroes.battles'));

        $this->assertSame(2, HeroBattleQueue::where('hero_id', $alice->hero->id)->value('size'));
        $content = $this->get(route('dominion.heroes.battles'))->assertOk()->getContent();
        $this->assertStringContainsString('data-queued="2"', $content);
        $this->assertStringNotContainsString('data-queue-size=', $content);
        $this->assertStringContainsString(str_replace('/', '\\/', route('api.dominion.hero-battles.status')), $content, 'Queued: polls for a match');
    }

    public function testTheBattleViewTellsTheAllyApartFromBorrowedHeroes(): void
    {
        [$alice, , $battle] = $this->twoVersusTwo();
        $view = app(BattlePresenter::class)->present($battle, $alice->id);
        $aliceSide = $this->sideOf($battle, $alice);

        $ally = $view->battle->combatant($aliceSide['ally']->id);
        $hero = $view->battle->combatant($aliceSide['hero']->id);

        $this->assertTrue($view->isAlly($ally));
        $this->assertFalse($view->isBorrowed($ally));
        $this->assertFalse($view->isAlly($hero));
        $this->assertSame([$hero->id, $ally->id], array_map(fn ($c) => $c->id, $view->commandable()), 'Your hero first');
    }
}
