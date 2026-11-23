<?php

namespace OpenDominion\Tests\Feature\HeroCombat;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\HeroHelper;
use OpenDominion\HeroCombat\Content\Loadouts\LoadoutSlot;
use OpenDominion\HeroCombat\Registry\CombatRegistry;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hero;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\Actions\HeroActionService;
use OpenDominion\Services\Dominion\HeroBattleService;
use OpenDominion\Tests\AbstractTestCase;

/**
 * The release defaults in config/heroes.php: only gear can be equipped and PvP battles grant
 * no daily experience.
 */
class HeroProgressionFlagsTest extends AbstractTestCase
{
    use DatabaseTransactions;

    protected Round $round;

    protected HeroCalculator $heroCalculator;

    protected HeroActionService $heroActionService;

    protected HeroBattleService $heroBattleService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->round = $this->createRound('-3 days midnight');
        $this->heroCalculator = $this->app->make(HeroCalculator::class);
        $this->heroActionService = $this->app->make(HeroActionService::class);
        $this->heroBattleService = $this->app->make(HeroBattleService::class);
    }

    protected function dominionWithHero(float $experience, array $loadout = [], bool $impersonate = false): Dominion
    {
        $user = $impersonate ? $this->createAndImpersonateUser() : $this->createUser();
        $dominion = $this->createDominionWithLegacyStats($user, $this->round, Race::where('name', 'Human')->firstOrFail());

        Hero::create([
            'dominion_id' => $dominion->id,
            'name' => 'Flag Hero ' . $dominion->id,
            'class' => 'blacksmith',
            'experience' => $experience,
            'class_data' => [],
            'loadout' => $loadout === [] ? null : $loadout,
        ]);

        return $dominion->refresh();
    }

    public function testOnlyGearSlotsAreEnabled(): void
    {
        $this->assertFalse($this->heroCalculator->isLoadoutSlotEnabled(LoadoutSlot::Technique));
        $this->assertFalse($this->heroCalculator->isLoadoutSlotEnabled(LoadoutSlot::Ability));
        $this->assertTrue($this->heroCalculator->isLoadoutSlotEnabled(LoadoutSlot::Gear));
    }

    public function testTechniquesAndAbilitiesAreNeverAvailable(): void
    {
        $hero = $this->dominionWithHero(10000)->hero;

        $this->assertSame([], $this->heroCalculator->getAvailableLoadoutOptions($hero, LoadoutSlot::Technique));
        $this->assertSame([], $this->heroCalculator->getAvailableLoadoutOptions($hero, LoadoutSlot::Ability));
        $this->assertSame(0, $this->heroCalculator->getLoadoutSlotCapacity($hero, LoadoutSlot::Technique));
        $this->assertSame(0, $this->heroCalculator->getLoadoutSlotCapacity($hero, LoadoutSlot::Ability));
        $this->assertCount(6, $this->heroCalculator->getAvailableLoadoutOptions($hero, LoadoutSlot::Gear));
        $this->assertSame(2, $this->heroCalculator->getLoadoutSlotCapacity($hero, LoadoutSlot::Gear));
    }

    public function testStoredTechniquesAndAbilitiesNeverReachABattle(): void
    {
        $dominion = $this->dominionWithHero(5000, ['technique' => 'backstab', 'ability' => 'forge', 'gear' => ['greatsword']]);

        $loadout = $this->heroCalculator->getLoadout($dominion->hero);
        $this->assertSame([], $loadout[LoadoutSlot::Technique->value]);
        $this->assertSame([], $loadout[LoadoutSlot::Ability->value]);
        $this->assertCount(1, $loadout[LoadoutSlot::Gear->value]);

        $battle = $this->heroBattleService->createPracticeBattle($dominion, 'rabid_bunny');
        $combatant = $battle->combatants()->where('hero_id', $dominion->hero->id)->firstOrFail();

        $this->assertNotContains('backstab', collect($combatant->effects)->pluck('key')->all());
        $this->assertNotContains('forge', $combatant->abilities);
        $this->assertEquals($this->heroCalculator->getBaseCombatStats(9)['attack'] + 2, $combatant->attack, 'Gear still applies');
    }

    public function testSavingATechniqueOrAbilityIsRejected(): void
    {
        $dominion = $this->dominionWithHero(10000);

        foreach ([['backstab', null], [null, 'forge']] as [$technique, $ability]) {
            try {
                $this->heroActionService->updateLoadout($dominion->refresh(), $technique, $ability, []);
                $this->fail('Disabled slots cannot be saved');
            } catch (GameException) {
                $this->assertNull($dominion->hero->fresh()->loadout);
            }
        }
    }

    public function testGearOnlyLoadoutsCanBeSaved(): void
    {
        $dominion = $this->dominionWithHero(3500);

        $this->heroActionService->updateLoadout($dominion, null, null, ['greatsword', 'hooded_cloak']);

        $this->assertEquals(['technique' => null, 'ability' => null, 'gear' => ['greatsword', 'hooded_cloak']], $dominion->hero->fresh()->loadout);
    }

    public function testOnlyGearCountsAsNewOrEmpty(): void
    {
        $hero = $this->dominionWithHero(1200)->hero;
        $this->assertEquals(['gear'], $this->heroCalculator->getNewLoadoutSlots($hero, 700), 'Level 3');
        $this->assertEquals(1, $this->heroCalculator->getEmptyLoadoutSlotCount($hero));

        $hero->experience = 3500;
        $this->assertEquals(['gear'], $this->heroCalculator->getNewLoadoutSlots($hero, 2900), 'Level 7: the second gear slot');
        $this->assertEquals(2, $this->heroCalculator->getEmptyLoadoutSlotCount($hero));

        $hero->experience = 2300;
        $this->assertSame([], $this->heroCalculator->getNewLoadoutSlots($hero, 1750), 'Level 5 opened only abilities');
    }

    public function testHeroesPageOffersOnlyGear(): void
    {
        $dominion = $this->dominionWithHero(10000, [], impersonate: true);
        $this->selectDominion($dominion);

        $content = $this->get(route('dominion.heroes'))->assertOk()->getContent();

        $this->assertStringNotContainsString('data-slot="technique"', $content);
        $this->assertStringNotContainsString('data-slot="ability"', $content);
        $this->assertStringNotContainsString('data-loadout-slot="technique"', $content);
        $this->assertStringNotContainsString('data-loadout-slot="ability"', $content);
        $this->assertStringContainsString('data-loadout-slot="gear"', $content);
        $this->assertMatchesRegularExpression('/name="gear\[\]"\s+id="loadout_greatsword"/', $content);
        $this->assertSame(2, substr_count($content, 'class="hero-tile hero-tile-locked"'), 'The technique and ability tiles');
    }

    public function testScribesListOnlyGear(): void
    {
        $this->createAndImpersonateUser();
        $heroHelper = $this->app->make(HeroHelper::class);
        $registry = $this->app->make(CombatRegistry::class);

        $content = $this->get(route('scribes.heroes'))->assertOk()->getContent();

        $this->assertStringContainsString($heroHelper->getLoadoutOptionName($registry->loadoutOption('greatsword')), $content);
        $this->assertStringNotContainsString('<td>' . $heroHelper->getLoadoutOptionName($registry->loadoutOption('riposte')) . '</td>', $content);
        $this->assertStringNotContainsString('<td>' . $heroHelper->getLoadoutOptionName($registry->loadoutOption('blade_flurry')) . '</td>', $content);
    }

    public function testFinishingAPvpBattleGrantsNoDailyExperience(): void
    {
        $alice = $this->dominionWithHero(2000);
        $bob = $this->dominionWithHero(2000);

        $battle = $this->heroBattleService->createBattle($alice, $bob);
        $battle->combatants()->update(['automated' => true]);
        $this->heroBattleService->processTurn($battle->fresh());
        $battle = $battle->fresh();

        $this->assertTrue($battle->finished);
        $this->assertEquals(2000, $alice->hero->fresh()->experience);
        $this->assertEquals(2000, $bob->hero->fresh()->experience);
        $this->assertNull($alice->hero->fresh()->last_pvp_reward_at);
        $this->assertCount(0, $battle->turns->flatMap(fn ($turn) => $turn->events)->where('type', 'reward'));
    }

    public function testOnlyUllyaothAndTheEvilTwinPairTakeAPartyAndBothRequireACompanion(): void
    {
        $encounters = collect(app(CombatRegistry::class)->encounters());

        $this->assertEquals(['evil_twin_2v2', 'ullyaoth'], $encounters->filter(fn ($encounter) => $encounter->maxPartySize() > 1)->keys()->all());
        $this->assertEquals(['evil_twin_2v2', 'ullyaoth'], $encounters->filter(fn ($encounter) => $encounter->minPartySize() > 1)->keys()->all());

        $dominion = $this->dominionWithHero(2000, [], impersonate: true);
        $this->dominionWithHero(2000);
        $this->selectDominion($dominion);

        $content = $this->get(route('dominion.heroes.battles.practice'))->assertOk()->getContent();

        $this->assertStringContainsString('id="practice-companion"', $content);
    }
}
