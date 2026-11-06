<?php

namespace OpenDominion\Tests\Feature\HeroCombat;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Exceptions\GameException;
use OpenDominion\HeroCombat\Content\AbstractEncounter;
use OpenDominion\HeroCombat\Engine\EncounterContext;
use OpenDominion\HeroCombat\Registry\CombatRegistry;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hero;
use OpenDominion\Models\HeroBattle;
use OpenDominion\Models\HeroCombatant;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\Actions\HeroActionService;
use OpenDominion\Services\Dominion\HeroBattleService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;
use OpenDominion\Tests\Traits\EnablesFullHeroProgression;

class BorrowedHeroTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;
    use EnablesFullHeroProgression;

    protected Round $round;

    protected Realm $realm;

    protected HeroBattleService $heroBattleService;

    protected HeroActionService $heroActionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableFullHeroProgression();

        $this->round = $this->createRound('-3 days midnight');
        $this->realm = $this->createRealm($this->round);
        $this->heroBattleService = $this->app->make(HeroBattleService::class);
        $this->heroActionService = $this->app->make(HeroActionService::class);

        $this->app->make(CombatRegistry::class)->registerEncounter(new class extends AbstractEncounter {
            public function key(): string
            {
                return 'test_party';
            }

            public function name(): string
            {
                return 'Test Party Encounter';
            }

            public function maxPartySize(): int
            {
                return 2;
            }

            public function roster(EncounterContext $context): array
            {
                return [['template' => 'gate_warden', 'name' => 'Gate Warden']];
            }
        });
    }

    protected function dominionWithHero(string $heroName, ?Realm $realm = null): Dominion
    {
        $dominion = $this->createDominionWithLegacyStats($this->createUser(), $this->round, Race::where('name', 'Human')->firstOrFail(), $realm ?? $this->realm);
        Hero::create([
            'dominion_id' => $dominion->id,
            'name' => $heroName,
            'class' => 'blacksmith',
            'experience' => 2000,
            'class_data' => [],
        ]);

        return $dominion->refresh();
    }

    protected function combatantOfHero(HeroBattle $battle, Hero $hero): HeroCombatant
    {
        return $battle->combatants()->where('hero_id', $hero->id)->firstOrFail();
    }

    public function testPilotBringsARealmmatesHero(): void
    {
        $pilot = $this->dominionWithHero('Pilot');
        $owner = $this->dominionWithHero('Lender');

        $battle = $this->heroBattleService->createPracticeBattle($pilot, 'test_party', [$owner->hero]);

        $borrowed = $this->combatantOfHero($battle, $owner->hero);
        $this->assertEquals(1, $borrowed->team);
        $this->assertEquals($owner->id, $borrowed->dominion_id, 'The hero still belongs to its owner');
        $this->assertEquals($pilot->id, $borrowed->controller_dominion_id);
        $this->assertEquals($pilot->id, $this->combatantOfHero($battle, $pilot->hero)->controller_dominion_id);
        $this->assertEquals(2, $battle->combatants()->where('team', 1)->count());
    }

    public function testCompanionsOnlyInPartyEncounters(): void
    {
        $pilot = $this->dominionWithHero('Pilot');
        $owner = $this->dominionWithHero('Lender');

        $this->expectException(GameException::class);
        $this->heroBattleService->createPracticeBattle($pilot, 'gate_warden', [$owner->hero]);
    }

    public function testCompanionsMustComeFromTheSameRealm(): void
    {
        $pilot = $this->dominionWithHero('Pilot');
        $stranger = $this->dominionWithHero('Stranger', $this->createRealm($this->round));

        $this->assertFalse($this->heroBattleService->availableCompanions($pilot)->contains('id', $stranger->hero->id));

        $this->expectException(GameException::class);
        $this->heroBattleService->createPracticeBattle($pilot, 'test_party', [$stranger->hero]);
    }

    public function testCannotBringYourOwnHeroTwice(): void
    {
        $pilot = $this->dominionWithHero('Pilot');

        $this->assertFalse($this->heroBattleService->availableCompanions($pilot)->contains('id', $pilot->hero->id));

        $this->expectException(GameException::class);
        $this->heroBattleService->createPracticeBattle($pilot, 'test_party', [$pilot->hero]);
    }

    public function testABorrowedHeroBringsItsOwnersLoadout(): void
    {
        $pilot = $this->dominionWithHero('Pilot');
        $owner = $this->dominionWithHero('Lender');
        $pilot->hero->update(['experience' => 5000, 'loadout' => ['technique' => 'feint']]);
        $owner->hero->update(['experience' => 5000, 'loadout' => ['technique' => 'backstab', 'ability' => 'forge', 'gear' => ['greatsword']]]);

        $battle = $this->heroBattleService->createPracticeBattle($pilot->fresh(), 'test_party', [$owner->hero->fresh()]);
        $borrowed = $this->combatantOfHero($battle, $owner->hero);

        $this->assertEquals($pilot->id, $borrowed->controller_dominion_id);
        $this->assertEquals(['backstab'], collect($borrowed->effects)->pluck('key')->all());
        $this->assertContains('forge', $borrowed->abilities);
        $this->assertEquals(42, $borrowed->attack);
    }

    public function testPracticePageHidesTheCompanionPickerUntilAPartyEncounterIsChosen(): void
    {
        $pilot = $this->dominionWithHero('Pilot');
        $this->dominionWithHero('Lender');
        $this->actingAs($pilot->user);
        $this->selectDominion($pilot);

        $content = $this->visitRoute('dominion.heroes.battles.practice')->response->getContent();

        $this->assertStringContainsString('<div id="practice-companion" class="d-none">', $content);
        $this->assertStringContainsString('value="test_party" data-party-size="2"', $content);
        $this->assertStringContainsString('value="heart_of_ice" data-party-size="1"', $content);
        $this->assertStringNotContainsString('(party encounters only)', $content);
    }

    public function testPracticePageListsCompanionsWithTheirTechniqueAndAbility(): void
    {
        $pilot = $this->dominionWithHero('Pilot');
        $equipped = $this->dominionWithHero('Lender');
        $unequipped = $this->dominionWithHero('Idle');
        $equipped->hero->update(['experience' => 5000, 'loadout' => ['technique' => 'backstab', 'ability' => 'forge', 'gear' => ['greatsword']]]);
        $this->actingAs($pilot->user);
        $this->selectDominion($pilot);

        $content = $this->visitRoute('dominion.heroes.battles.practice')->response->getContent();

        $this->assertMatchesRegularExpression('/>Lender \([^<]*\) - Backstab, Forge<\/option>/', $content);
        $this->assertMatchesRegularExpression('/>Idle \([^<)]*\)<\/option>/', $content);
        $this->assertStringNotContainsString('Greatsword', $content);
        $this->assertStringNotContainsString('Blacksmith', $content);
    }

    public function testPilotControlsTheBorrowedHeroAndTheOwnerDoesNot(): void
    {
        $pilot = $this->dominionWithHero('Pilot');
        $owner = $this->dominionWithHero('Lender');
        $battle = $this->heroBattleService->createPracticeBattle($pilot, 'test_party', [$owner->hero]);
        $borrowed = $this->combatantOfHero($battle, $owner->hero);

        $this->heroActionService->queueAction($pilot, $borrowed, null, 'defend');
        $this->assertCount(1, $borrowed->refresh()->actions);

        $this->expectException(GameException::class);
        $this->heroActionService->queueAction($owner, $borrowed->refresh(), null, 'defend');
    }

    public function testTurnWaitsForBothPilotedHeroes(): void
    {
        $pilot = $this->dominionWithHero('Pilot');
        $owner = $this->dominionWithHero('Lender');
        $battle = $this->heroBattleService->createPracticeBattle($pilot, 'test_party', [$owner->hero]);
        $warden = $battle->combatants()->whereNull('hero_id')->firstOrFail();

        $this->heroActionService->queueAction($pilot, $this->combatantOfHero($battle, $pilot->hero), $warden, 'attack');
        $this->assertFalse($this->heroBattleService->processTurn($battle->fresh()));

        $this->heroActionService->queueAction($pilot, $this->combatantOfHero($battle, $owner->hero), $this->combatantOfHero($battle, $pilot->hero), 'defend');
        $this->assertTrue($this->heroBattleService->processTurn($battle->fresh()));
    }

    public function testOwnerIsNotBlockedOrShownTheBorrowedBattle(): void
    {
        $pilot = $this->dominionWithHero('Pilot');
        $owner = $this->dominionWithHero('Lender');
        $this->heroBattleService->createPracticeBattle($pilot, 'test_party', [$owner->hero]);

        $this->assertTrue($this->heroBattleService->hasBattleInProgress($pilot));
        $this->assertFalse($this->heroBattleService->hasBattleInProgress($owner));
        $this->assertEquals(0, HeroBattle::query()->controlledBy($owner)->count());

        $ownBattle = $this->heroBattleService->createPracticeBattle($owner, 'gate_warden');
        $this->assertNotNull($ownBattle->id);
    }

    public function testAHeroCanBeBorrowedByTwoRealmmatesAtOnce(): void
    {
        $owner = $this->dominionWithHero('Lender');
        $first = $this->dominionWithHero('First');
        $second = $this->dominionWithHero('Second');

        $a = $this->heroBattleService->createPracticeBattle($first, 'test_party', [$owner->hero]);
        $b = $this->heroBattleService->createPracticeBattle($second, 'test_party', [$owner->hero]);

        $this->assertEquals($first->id, $this->combatantOfHero($a, $owner->hero)->controller_dominion_id);
        $this->assertEquals($second->id, $this->combatantOfHero($b, $owner->hero)->controller_dominion_id);
    }

    public function testBattlePageShowsAPanelForEachPilotedHero(): void
    {
        $pilot = $this->dominionWithHero('Pilot');
        $owner = $this->dominionWithHero('Lender');
        $this->heroBattleService->createPracticeBattle($pilot, 'test_party', [$owner->hero]);
        $this->actingAs($pilot->user);
        $this->selectDominion($pilot);

        $this->visitRoute('dominion.heroes.battles')
            ->see('Lender')
            ->see('Borrowed')
            ->see('Turn 1 orders')
            ->see('0 of 2 chosen')
            ->see("Automate all of Lender's turns")
            ->see('Automate all of my turns');
    }

    public function testPracticePageOffersRealmmatesForPartyEncounters(): void
    {
        $pilot = $this->dominionWithHero('Pilot');
        $this->dominionWithHero('Lender');
        $this->actingAs($pilot->user);
        $this->selectDominion($pilot);

        $this->visitRoute('dominion.heroes.battles.practice')
            ->see("Bring a realmmate's hero")
            ->see('Party of 2')
            ->see('Lender');
    }

    /**
     * @return array{0: Dominion, 1: Dominion, 2: HeroBattle, 3: HeroCombatant, 4: HeroCombatant, 5: HeroCombatant}
     */
    protected function party(): array
    {
        $pilot = $this->dominionWithHero('Pilot');
        $owner = $this->dominionWithHero('Lender');
        $battle = $this->heroBattleService->createPracticeBattle($pilot, 'test_party', [$owner->hero]);

        return [
            $pilot,
            $owner,
            $battle,
            $this->combatantOfHero($battle, $pilot->hero),
            $this->combatantOfHero($battle, $owner->hero),
            $battle->combatants()->whereNull('hero_id')->firstOrFail(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>> the turn's selected events
     */
    protected function selections(HeroBattle $battle, int $turn): array
    {
        $row = $battle->turns()->where('turn', $turn)->firstOrFail();

        return array_values(array_filter($row->events, fn (array $event) => $event['type'] === 'selected'));
    }

    public function testLockingInOrdersForEveryPilotedHeroResolvesTheTurnOnce(): void
    {
        [$pilot, , $battle, $hero, $borrowed, $warden] = $this->party();

        $this->heroActionService->queueOrders($pilot, $battle, [
            $hero->id => ['action' => 'attack', 'target' => $warden->id],
            $borrowed->id => ['action' => 'defend', 'target' => $hero->id],
        ]);
        $this->assertTrue($this->heroBattleService->processTurn($battle->fresh()));

        $this->assertEquals(2, $battle->fresh()->current_turn);
        $chosen = collect($this->selections($battle, 1))->keyBy('actor');
        $this->assertEquals(['attack', $warden->id], [$chosen[$hero->id]['ability'], $chosen[$hero->id]['target']]);
        $this->assertEquals(['defend', $hero->id], [$chosen[$borrowed->id]['ability'], $chosen[$borrowed->id]['target']]);
    }

    public function testRallyOrderReachesTheAllysAttack(): void
    {
        [$pilot, , $battle, $hero, $borrowed, $warden] = $this->party();

        $this->heroActionService->queueOrders($pilot, $battle, [
            $hero->id => ['action' => 'attack', 'target' => $warden->id],
            $borrowed->id => ['action' => 'focus', 'target' => $hero->id],
        ]);
        $this->assertTrue($this->heroBattleService->processTurn($battle->fresh()));

        $chosen = collect($this->selections($battle, 1))->keyBy('actor');
        $this->assertEquals(['focus', $hero->id], [$chosen[$borrowed->id]['ability'], $chosen[$borrowed->id]['target']]);

        $hit = collect($battle->turns()->where('turn', 1)->firstOrFail()->events)
            ->first(fn (array $event) => $event['type'] === 'damage' && $event['source'] === $hero->id);
        $this->assertEquals($borrowed->focus, $hit['bonus'], 'The Rally bonus reaches the attack, whatever the warden chose');
    }

    public function testFocusedHeroCanRallyButCannotFocusThemselvesAgain(): void
    {
        [$pilot, , $battle, $hero, $borrowed] = $this->party();

        $this->heroActionService->queueOrders($pilot, $battle, [
            $hero->id => ['action' => 'focus', 'target' => null],
            $borrowed->id => ['action' => 'defend', 'target' => null],
        ]);
        $this->assertTrue($this->heroBattleService->processTurn($battle->fresh()));

        $this->heroActionService->queueOrders($pilot, $battle->fresh(), [
            $hero->id => ['action' => 'counter', 'target' => null],
            $borrowed->id => ['action' => 'defend', 'target' => null],
        ]);
        $this->assertTrue($this->heroBattleService->processTurn($battle->fresh()));

        try {
            $this->heroActionService->queueOrders($pilot, $battle->fresh(), [
                $hero->id => ['action' => 'focus', 'target' => null],
                $borrowed->id => ['action' => 'defend', 'target' => null],
            ]);
            $this->fail('Expected a Focused hero focusing themselves to be rejected');
        } catch (GameException) {
            $this->assertSame([], $hero->fresh()->actions ?? []);
        }

        $this->heroActionService->queueOrders($pilot, $battle->fresh(), [
            $hero->id => ['action' => 'focus', 'target' => $borrowed->id],
            $borrowed->id => ['action' => 'defend', 'target' => null],
        ]);
        $this->assertEquals('focus', $hero->fresh()->actions[0]['ability']);
    }

    public function testLockInNeedsAnOrderForEveryPilotedHero(): void
    {
        [$pilot, , $battle, $hero, $borrowed, $warden] = $this->party();

        try {
            $this->heroActionService->queueOrders($pilot, $battle, [$hero->id => ['action' => 'attack', 'target' => $warden->id]]);
            $this->fail('Expected the missing order to be rejected');
        } catch (GameException $e) {
            $this->assertEquals('Choose an action for Lender before locking in.', $e->getMessage());
        }

        $this->assertSame([], $hero->fresh()->actions ?? []);
        $this->assertSame([], $borrowed->fresh()->actions ?? []);
    }

    public function testLockInIsAllOrNothing(): void
    {
        [$pilot, , $battle, $hero, $borrowed, $warden] = $this->party();

        try {
            $this->heroActionService->queueOrders($pilot, $battle, [
                $hero->id => ['action' => 'attack', 'target' => $warden->id],
                $borrowed->id => ['action' => 'attack', 'target' => $hero->id],
            ]);
            $this->fail('Expected attacking an ally to be rejected');
        } catch (GameException $e) {
            $this->assertEquals('Invalid target.', $e->getMessage());
        }

        $this->assertSame([], $hero->fresh()->actions ?? [], 'The valid order is rolled back too');
        $this->assertEquals(1, $battle->fresh()->current_turn);
    }

    public function testLockInSkipsHeroesOnAutopilot(): void
    {
        [$pilot, , $battle, $hero, $borrowed, $warden] = $this->party();
        $borrowed->update(['automated' => true]);

        $this->heroActionService->queueOrders($pilot, $battle, [$hero->id => ['action' => 'attack', 'target' => $warden->id]]);
        $this->assertTrue($this->heroBattleService->processTurn($battle->fresh()));

        $auto = collect($this->selections($battle, 1))->firstWhere('actor', $borrowed->id);
        $this->assertEquals('ai', $auto['source']);
    }

    public function testLockInRejectsHeroesTheDominionDoesNotPilot(): void
    {
        [, $owner, $battle, $hero, $borrowed] = $this->party();

        $this->expectException(GameException::class);
        $this->expectExceptionMessage('You cannot queue actions for another hero.');

        $this->heroActionService->queueOrders($owner, $battle, [$borrowed->id => ['action' => 'defend']]);
    }

    public function testLockInRejectsFinishedBattles(): void
    {
        [$pilot, , $battle, $hero, $borrowed] = $this->party();
        $battle->update(['finished' => true]);

        $this->expectException(GameException::class);
        $this->expectExceptionMessage('You cannot queue actions for a battle that has ended.');

        $this->heroActionService->queueOrders($pilot, $battle->fresh(), [$hero->id => ['action' => 'defend'], $borrowed->id => ['action' => 'defend']]);
    }

    public function testLockInRouteQueuesAndResolves(): void
    {
        [$pilot, , $battle, $hero, $borrowed, $warden] = $this->party();
        $this->actingAs($pilot->user);
        $this->selectDominion($pilot);

        $this->post(route('dominion.heroes.battles.orders'), [
            'battle' => $battle->id,
            'orders' => [
                $hero->id => ['action' => 'attack', 'target' => $warden->id],
                $borrowed->id => ['action' => 'recover', 'target' => ''],
            ],
        ])->assertRedirectedToRoute('dominion.heroes.battles');

        $this->assertEquals(2, $battle->fresh()->current_turn);
        $this->assertEquals('Turn 1 resolved.', session('alert-success'));
    }

    public function testLockInRouteNeedsOrders(): void
    {
        [$pilot, , $battle] = $this->party();
        $this->actingAs($pilot->user);
        $this->selectDominion($pilot);

        $this->post(route('dominion.heroes.battles.orders'), ['battle' => $battle->id]);

        $this->assertSessionHasErrors(['orders']);
        $this->assertEquals(1, $battle->fresh()->current_turn);
    }
}
