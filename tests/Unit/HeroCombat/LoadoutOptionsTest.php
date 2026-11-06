<?php

namespace OpenDominion\Tests\Unit\HeroCombat;

use OpenDominion\Helpers\HeroHelper;
use OpenDominion\HeroCombat\Content\Loadouts\HeroClassLoadouts;
use OpenDominion\HeroCombat\Content\Loadouts\HeroLoadoutOptions;
use OpenDominion\HeroCombat\Content\Loadouts\LoadoutOption;
use OpenDominion\HeroCombat\Content\Loadouts\LoadoutSlot;
use OpenDominion\HeroCombat\Engine\Stats\Stat;
use OpenDominion\HeroCombat\Registry\CombatRegistry;
use OpenDominion\Tests\AbstractTestCase;

class LoadoutOptionsTest extends AbstractTestCase
{
    protected function registry(): CombatRegistry
    {
        return $this->app->make(CombatRegistry::class);
    }

    public function testSlotsHoldOneTechniqueOneAbilityAndTwoGear(): void
    {
        $this->assertEquals(1, LoadoutSlot::Technique->capacity());
        $this->assertEquals(1, LoadoutSlot::Ability->capacity());
        $this->assertEquals(2, LoadoutSlot::Gear->capacity());
    }

    public function testEveryTechniqueIsImplemented(): void
    {
        $techniques = $this->registry()->loadoutOptions(LoadoutSlot::Technique);

        $this->assertCount(12, $techniques);
        foreach ($techniques as $key => $option) {
            $this->assertTrue($this->registry()->hasEffect($key), "Technique {$key} has no effect class");
        }
    }

    public function testEveryAbilityIsImplementedAndIsASignatureOrOpenToEveryClass(): void
    {
        $loadouts = new HeroClassLoadouts();
        $abilities = $this->registry()->loadoutOptions(LoadoutSlot::Ability);

        $this->assertCount(8, $abilities);
        foreach ($abilities as $key => $option) {
            $this->assertTrue($this->registry()->hasAbility($key) || $this->registry()->hasEffect($key), "Ability {$key} has no ability or effect class");
            $expectedLevel = $loadouts->classFor($key) === null ? HeroLoadoutOptions::UNIVERSAL_ABILITY_LEVEL : HeroClassLoadouts::UNLOCK_CLASS_LEVEL;
            $this->assertSame($expectedLevel, $option->level, "{$key} is on the wrong level");
        }

        $universal = array_keys(array_filter($abilities, fn (LoadoutOption $option) => $loadouts->classFor($option->key) === null));
        $this->assertEqualsCanonicalizing(['blade_flurry', 'whirlwind'], $universal);
    }

    public function testEveryClassSignatureIsAnAbilityOrTechniqueWithTheClassIcon(): void
    {
        $classes = $this->app->make(HeroHelper::class)->getClasses();

        foreach (HeroClassLoadouts::SIGNATURES as $class => $key) {
            $this->assertTrue($this->registry()->hasLoadoutOption($key), "{$class} signature {$key} has no loadout option");

            $option = $this->registry()->loadoutOption($key);
            $this->assertContains($option->slot, [LoadoutSlot::Ability, LoadoutSlot::Technique]);
            $this->assertSame(HeroClassLoadouts::UNLOCK_CLASS_LEVEL, $option->level, "{$key} unlocks at class level 5");
            $this->assertSame($classes[$class]['icon'], $option->icon, "{$key} should use the {$class} icon");
        }
    }

    public function testOptionsSitOnTheirRequiredLevels(): void
    {
        $expected = [
            LoadoutSlot::Technique->value => [3, 7, HeroClassLoadouts::UNLOCK_CLASS_LEVEL],
            LoadoutSlot::Ability->value => [HeroClassLoadouts::UNLOCK_CLASS_LEVEL, HeroLoadoutOptions::UNIVERSAL_ABILITY_LEVEL],
            LoadoutSlot::Gear->value => [3],
        ];

        foreach ($this->registry()->loadoutOptions() as $key => $option) {
            $this->assertContains($option->level, $expected[$option->slot->value], "{$key} is on the wrong level");
        }
    }

    public function testOnlyGearHasStatsAndAName(): void
    {
        $stats = array_map(fn (Stat $stat) => $stat->value, Stat::cases());

        foreach ($this->registry()->loadoutOptions() as $key => $option) {
            if ($option->slot !== LoadoutSlot::Gear) {
                $this->assertSame([], $option->stats, "{$key} grants stats; only gear may");
                $this->assertNull($option->name, "{$key} takes its name from its effect or ability");
                continue;
            }

            $this->assertNotNull($option->name, "Gear {$key} needs a name");
            $this->assertNotEmpty($option->stats, "Gear {$key} needs stats");
            $this->assertSame([], array_diff(array_keys($option->stats), $stats), "Gear {$key} has an unknown stat");
        }
    }

    public function testOptionsCanBeListedBySlot(): void
    {
        $gear = $this->registry()->loadoutOptions(LoadoutSlot::Gear);

        $this->assertCount(6, $gear);
        $this->assertContainsOnlyInstancesOf(LoadoutOption::class, $gear);
        $this->assertArrayHasKey('greatsword', $gear);
        $this->assertArrayNotHasKey('backstab', $gear);
    }
}
