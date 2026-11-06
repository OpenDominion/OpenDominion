<?php

namespace OpenDominion\Tests\Feature\HeroCombat;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\NotificationHelper;
use OpenDominion\HeroCombat\Content\Loadouts\HeroClassLoadouts;
use OpenDominion\HeroCombat\Content\Loadouts\LoadoutOption;
use OpenDominion\HeroCombat\Content\Loadouts\LoadoutSlot;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hero;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Notifications\WebNotification;
use OpenDominion\Services\Dominion\Actions\HeroActionService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;
use OpenDominion\Tests\Traits\EnablesFullHeroProgression;

class HeroLoadoutTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;
    use EnablesFullHeroProgression;

    protected Round $round;

    protected HeroActionService $heroActionService;

    protected HeroCalculator $heroCalculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableFullHeroProgression();

        $this->round = $this->createRound('-3 days midnight');
        $this->heroActionService = $this->app->make(HeroActionService::class);
        $this->heroCalculator = $this->app->make(HeroCalculator::class);
    }

    /**
     * @param array<string, array<string, mixed>> $classData
     */
    protected function dominionWithHero(float $experience, string $class = 'blacksmith', array $classData = []): Dominion
    {
        $dominion = $this->createDominionWithLegacyStats($this->createUser(), $this->round, Race::where('name', 'Human')->firstOrFail());

        Hero::create([
            'dominion_id' => $dominion->id,
            'name' => 'Loadout Hero',
            'class' => $class,
            'experience' => $experience,
            'class_data' => $classData,
        ]);

        return $dominion->refresh();
    }

    /**
     * @return string[]
     */
    protected function availableKeys(Hero $hero, LoadoutSlot $slot): array
    {
        return array_keys($this->heroCalculator->getAvailableLoadoutOptions($hero, $slot));
    }

    /**
     * @return string[]
     */
    protected function equippedKeys(Hero $hero, LoadoutSlot $slot): array
    {
        return array_map(fn (LoadoutOption $option) => $option->key, $this->heroCalculator->getLoadout($hero)[$slot->value]);
    }

    public function testTechniquesOpenAtLevelsThreeAndSeven(): void
    {
        $this->assertSame([], $this->availableKeys($this->dominionWithHero(700)->hero, LoadoutSlot::Technique), 'Level 2');

        $levelThree = $this->availableKeys($this->dominionWithHero(1200)->hero, LoadoutSlot::Technique);
        $this->assertCount(5, $levelThree);
        $this->assertContains('backstab', $levelThree);
        $this->assertNotContains('riposte', $levelThree);

        $this->assertCount(5, $this->availableKeys($this->dominionWithHero(2900)->hero, LoadoutSlot::Technique), 'Level 6');

        $levelSeven = $this->availableKeys($this->dominionWithHero(3500)->hero, LoadoutSlot::Technique);
        $this->assertCount(9, $levelSeven);
        $this->assertContains('riposte', $levelSeven);
    }

    public function testGearOpensAtLevelThree(): void
    {
        $this->assertSame([], $this->availableKeys($this->dominionWithHero(700)->hero, LoadoutSlot::Gear), 'Level 2');
        $this->assertCount(6, $this->availableKeys($this->dominionWithHero(1200)->hero, LoadoutSlot::Gear));
    }

    public function testClassAbilitiesOpenAtClassLevelFiveWithoutAHeroLevelRequirement(): void
    {
        $this->assertSame([], $this->availableKeys($this->dominionWithHero(1750)->hero, LoadoutSlot::Ability), 'Level 4');
        $this->assertEqualsCanonicalizing(['forge', 'blade_flurry', 'whirlwind'], $this->availableKeys($this->dominionWithHero(2300)->hero, LoadoutSlot::Ability), 'Only the class played, plus the abilities open to every class');
    }

    public function testHealersAndSorcerersUnlockTheirTechniqueAtClassLevelFive(): void
    {
        $this->assertNotContains('mending', $this->availableKeys($this->dominionWithHero(1750, 'healer')->hero, LoadoutSlot::Technique), 'Healer level 4');
        $this->assertContains('mending', $this->availableKeys($this->dominionWithHero(2300, 'healer')->hero, LoadoutSlot::Technique));
        $this->assertContains('channeling', $this->availableKeys($this->dominionWithHero(2300, 'sorcerer')->hero, LoadoutSlot::Technique));

        $blacksmith = $this->availableKeys($this->dominionWithHero(5000)->hero, LoadoutSlot::Technique);
        $this->assertNotContains('mending', $blacksmith, 'Only for Healers');
        $this->assertNotContains('channeling', $blacksmith, 'Only for Sorcerers');
    }

    public function testEveryClassCanEquipBladeFlurryAndWhirlwindAtLevelFive(): void
    {
        foreach (array_keys(HeroClassLoadouts::SIGNATURES) as $class) {
            $this->assertSame([], $this->availableKeys($this->dominionWithHero(1750, $class)->hero, LoadoutSlot::Ability), "{$class} at level 4");

            $available = $this->availableKeys($this->dominionWithHero(2300, $class)->hero, LoadoutSlot::Ability);
            $this->assertContains('blade_flurry', $available, $class);
            $this->assertContains('whirlwind', $available, $class);
        }
    }

    public function testClassAbilitiesIncludeInactiveClassesAtLevelFive(): void
    {
        $hero = $this->dominionWithHero(2300, 'blacksmith', [
            'farmer' => ['key' => 'farmer', 'level' => 5, 'experience' => 2300],
            'alchemist' => ['key' => 'alchemist', 'level' => 4, 'experience' => 1750],
        ])->hero;

        $this->assertEqualsCanonicalizing(['forge', 'blade_flurry', 'whirlwind'], $this->availableKeys($hero, LoadoutSlot::Ability));
        $this->assertContains('hardiness', $this->availableKeys($hero, LoadoutSlot::Technique), 'Farmer level 5, inactive');
        $this->assertNotContains('volatile_mixture', $this->availableKeys($hero, LoadoutSlot::Ability), 'Alchemist level 4');
    }

    public function testChangingClassKeepsAccessAndTheLoadout(): void
    {
        $dominion = $this->dominionWithHero(10000);
        $this->heroActionService->updateLoadout($dominion, 'riposte', 'forge', ['greatsword', 'hooded_cloak']);

        $this->heroActionService->changeClass($dominion->refresh(), 'farmer');

        $hero = $dominion->refresh()->hero;
        $this->assertEquals(0, $this->heroCalculator->getHeroLevel($hero));
        $this->assertEquals(['riposte'], $this->equippedKeys($hero, LoadoutSlot::Technique));
        $this->assertEquals(['forge'], $this->equippedKeys($hero, LoadoutSlot::Ability));
        $this->assertEquals(['greatsword', 'hooded_cloak'], $this->equippedKeys($hero, LoadoutSlot::Gear));
    }

    public function testUpdatingTheLoadoutStoresEverySlot(): void
    {
        $dominion = $this->dominionWithHero(10000);

        $result = $this->heroActionService->updateLoadout($dominion, 'backstab', 'forge', ['greatsword', 'hooded_cloak']);

        $this->assertEquals('Your combat loadout has been updated.', $result['message']);
        $this->assertEquals(
            ['technique' => 'backstab', 'ability' => 'forge', 'gear' => ['greatsword', 'hooded_cloak']],
            $dominion->hero->fresh()->loadout
        );
    }

    public function testSlotsCanBeClearedAndSwappedFreely(): void
    {
        $dominion = $this->dominionWithHero(10000);
        $this->heroActionService->updateLoadout($dominion, 'backstab', 'forge', ['greatsword', 'hooded_cloak']);

        $this->heroActionService->updateLoadout($dominion->refresh(), 'feint', '', []);
        $this->heroActionService->updateLoadout($dominion->refresh(), 'riposte', null, ['crystal_wand']);

        $this->assertEquals(
            ['technique' => 'riposte', 'ability' => null, 'gear' => ['crystal_wand']],
            $dominion->hero->fresh()->loadout
        );
    }

    public function testCannotEquipOptionsAboveTheHerosLevel(): void
    {
        $dominion = $this->dominionWithHero(2900); // Level 6

        $this->expectException(GameException::class);
        $this->heroActionService->updateLoadout($dominion, 'riposte', null, []);
    }

    public function testCannotEquipAnotherClassesAbility(): void
    {
        $dominion = $this->dominionWithHero(5000);

        $this->expectException(GameException::class);
        $this->heroActionService->updateLoadout($dominion, null, 'shadow_strike', []);
    }

    public function testCannotEquipAnOptionInTheWrongSlot(): void
    {
        $dominion = $this->dominionWithHero(5000);

        $this->expectException(GameException::class);
        $this->heroActionService->updateLoadout($dominion, 'greatsword', null, []);
    }

    public function testCannotEquipUnknownOptions(): void
    {
        $dominion = $this->dominionWithHero(5000);

        $this->expectException(GameException::class);
        $this->heroActionService->updateLoadout($dominion, 'overpower', null, []);
    }

    public function testCannotEquipTwoOfTheSamePieceOfGear(): void
    {
        $dominion = $this->dominionWithHero(10000);

        $this->expectException(GameException::class);
        $this->expectExceptionMessage('one of each piece of gear');
        $this->heroActionService->updateLoadout($dominion, null, null, ['greatsword', 'greatsword']);
    }

    public function testCannotBringThreePiecesOfGear(): void
    {
        $dominion = $this->dominionWithHero(10000);

        $this->expectException(GameException::class);
        $this->expectExceptionMessage('2 pieces of gear');
        $this->heroActionService->updateLoadout($dominion, null, null, ['greatsword', 'swordbreaker', 'hooded_cloak']);
    }

    public function testTheSecondPieceOfGearOpensAtLevelSeven(): void
    {
        $dominion = $this->dominionWithHero(2900); // Level 6

        try {
            $this->heroActionService->updateLoadout($dominion, null, null, ['greatsword', 'hooded_cloak']);
            $this->fail('A level 6 hero brings one piece of gear');
        } catch (GameException) {
            $this->assertNull($dominion->hero->fresh()->loadout);
        }

        $dominion->hero->update(['experience' => 3500]);
        $this->heroActionService->updateLoadout($dominion->refresh(), null, null, ['greatsword', 'hooded_cloak']);
        $this->assertEquals(['greatsword', 'hooded_cloak'], $this->equippedKeys($dominion->hero->fresh(), LoadoutSlot::Gear));
    }

    public function testGearCannotTakeEvasionBelowZero(): void
    {
        $dominion = $this->dominionWithHero(10000);

        $this->expectException(GameException::class);
        $this->heroActionService->updateLoadout($dominion, null, null, ['greatsword', 'plate_armor']);
    }

    public function testAStoredLoadoutThatTakesEvasionBelowZeroKeepsOnlyTheFirstPiece(): void
    {
        $hero = $this->dominionWithHero(10000)->hero;
        $hero->loadout = ['gear' => ['greatsword', 'plate_armor']];

        $this->assertEquals(['greatsword'], $this->equippedKeys($hero, LoadoutSlot::Gear));
    }

    public function testRejectedChangesLeaveTheLoadoutAlone(): void
    {
        $dominion = $this->dominionWithHero(5000);
        $this->heroActionService->updateLoadout($dominion, 'backstab', null, []);

        try {
            $this->heroActionService->updateLoadout($dominion->refresh(), 'feint', null, ['greatsword', 'greatsword']);
            $this->fail('Duplicate gear should be rejected');
        } catch (GameException) {
            $this->assertEquals('backstab', $dominion->hero->fresh()->loadout['technique']);
        }
    }

    public function testLockedDominionsCannotChangeTheLoadout(): void
    {
        $dominion = $this->dominionWithHero(5000);
        $dominion->locked_at = now();
        $dominion->save();

        $this->expectException(GameException::class);
        $this->heroActionService->updateLoadout($dominion->refresh(), 'backstab', null, []);
    }

    public function testDominionsWithoutAHeroCannotSetALoadout(): void
    {
        $dominion = $this->createDominionWithLegacyStats($this->createUser(), $this->round, Race::where('name', 'Human')->firstOrFail());

        $this->expectException(GameException::class);
        $this->heroActionService->updateLoadout($dominion, 'backstab', null, []);
    }

    public function testStoredOptionsTheHeroCannotUseAreIgnored(): void
    {
        $hero = $this->dominionWithHero(700)->hero;
        $hero->loadout = ['technique' => 'overpower', 'ability' => 'hardiness', 'gear' => ['greatsword']];

        $this->assertSame([], $this->equippedKeys($hero, LoadoutSlot::Technique));
        $this->assertSame([], $this->equippedKeys($hero, LoadoutSlot::Ability));
        $this->assertSame([], $this->equippedKeys($hero, LoadoutSlot::Gear));
        $this->assertEquals($this->heroCalculator->getBaseCombatStats(2), $this->heroCalculator->getHeroCombatStats($hero));
    }

    public function testBothPiecesOfGearChangeCombatStats(): void
    {
        $hero = $this->dominionWithHero(10000)->hero;
        $base = $this->heroCalculator->getBaseCombatStats(12);

        $hero->loadout = ['gear' => ['greatsword', 'swordbreaker']];
        $stats = $this->heroCalculator->getHeroCombatStats($hero);

        $this->assertEquals($base['attack'] + 2, $stats['attack']);
        $this->assertEquals($base['evasion'] - 10, $stats['evasion']);
        $this->assertEquals($base['counter'] + 6, $stats['counter']);
        $this->assertEquals(['attack' => 2, 'evasion' => -10, 'counter' => 6], $this->heroCalculator->getLoadoutStatBonuses($hero));
    }

    public function testEmptySlotCountOnlyIncludesSlotsTheHeroCanFill(): void
    {
        $this->assertEquals(0, $this->heroCalculator->getEmptyLoadoutSlotCount(null));
        $this->assertEquals(0, $this->heroCalculator->getEmptyLoadoutSlotCount($this->dominionWithHero(700)->hero), 'Level 2');
        $this->assertEquals(2, $this->heroCalculator->getEmptyLoadoutSlotCount($this->dominionWithHero(1200)->hero), 'Level 3: technique, one gear');
        $this->assertEquals(3, $this->heroCalculator->getEmptyLoadoutSlotCount($this->dominionWithHero(2300)->hero), 'Level 5: technique, ability, one gear');
        $this->assertEquals(4, $this->heroCalculator->getEmptyLoadoutSlotCount($this->dominionWithHero(3500)->hero), 'Level 7: everything');
    }

    public function testFillingSlotsReducesTheEmptySlotCount(): void
    {
        $hero = $this->dominionWithHero(2300)->hero;

        $hero->loadout = ['technique' => 'backstab', 'gear' => ['greatsword']];
        $this->assertEquals(1, $this->heroCalculator->getEmptyLoadoutSlotCount($hero));

        $hero->loadout = ['technique' => 'backstab', 'ability' => 'forge', 'gear' => ['greatsword']];
        $this->assertEquals(0, $this->heroCalculator->getEmptyLoadoutSlotCount($hero), 'One gear slot at level 5');
    }

    public function testNewLoadoutSlotsFollowEachRequirement(): void
    {
        $hero = $this->dominionWithHero(5000)->hero;

        $hero->experience = 1200;
        $this->assertEquals(['technique', 'gear'], $this->heroCalculator->getNewLoadoutSlots($hero, 700));

        $hero->experience = 2300;
        $this->assertEquals(['ability'], $this->heroCalculator->getNewLoadoutSlots($hero, 1750));

        $hero->experience = 3500;
        $this->assertEquals(['technique', 'gear'], $this->heroCalculator->getNewLoadoutSlots($hero, 2900), 'Technique II and the second gear slot');

        $hero->experience = 5000;
        $this->assertSame([], $this->heroCalculator->getNewLoadoutSlots($hero, 4250));

        $hero->experience = 1750;
        $this->assertSame([], $this->heroCalculator->getNewLoadoutSlots($hero, 1200));
    }

    public function testReachingClassLevelFiveOnAHighLevelHeroCountsAsNew(): void
    {
        $hero = $this->dominionWithHero(2300, 'alchemist', [
            'blacksmith' => ['key' => 'blacksmith', 'level' => 9, 'experience' => 5000],
        ])->hero;

        $this->assertEquals(['ability'], $this->heroCalculator->getNewLoadoutSlots($hero, 1750));
    }

    public function testLevelUpNotificationMentionsNewCombatOptions(): void
    {
        $dominion = $this->dominionWithHero(700);
        $hero = $dominion->hero;

        $hero->experience = 1200;
        $hero->save();

        Notification::assertSentTo($dominion, WebNotification::class, function (WebNotification $notification) use ($dominion) {
            $data = $notification->toArray($dominion);

            return $data['data']['unlocks'] === ['technique', 'gear']
                && $data['message'] === 'Your hero reached level 3. New techniques and gear available for your combat loadout.';
        });
    }

    public function testLevelUpNotificationStaysPlainWithoutNewOptions(): void
    {
        $dominion = $this->dominionWithHero(1200);
        $hero = $dominion->hero;

        $hero->experience = 1750;
        $hero->save();

        Notification::assertSentTo($dominion, WebNotification::class, function (WebNotification $notification) use ($dominion) {
            return $notification->toArray($dominion)['message'] === 'Your hero reached level 4.';
        });
    }

    public function testNotificationMessagesHandleOldAndCombinedData(): void
    {
        $helper = $this->app->make(NotificationHelper::class);

        $this->assertEquals(
            'Your hero reached level 4.',
            $helper->getNotificationMessage('irregular_dominion', 'hero_level', ['level' => 4]),
            'Notifications stored before loadouts have no unlocks'
        );
        $this->assertEquals(
            'Your hero reached level 9. New techniques, abilities and gear available for your combat loadout.',
            $helper->getNotificationMessage('irregular_dominion', 'hero_level', ['level' => 9, 'unlocks' => ['technique', 'ability', 'gear']])
        );
    }
}
