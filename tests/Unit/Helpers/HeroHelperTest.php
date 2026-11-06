<?php

namespace OpenDominion\Tests\Unit\Helpers;

use OpenDominion\Helpers\HeroHelper;
use OpenDominion\HeroCombat\Content\Loadouts\LoadoutSlot;
use OpenDominion\HeroCombat\Registry\CombatRegistry;
use OpenDominion\Models\Hero;
use OpenDominion\Tests\AbstractBrowserKitTestCase;
use OpenDominion\Tests\Traits\EnablesFullHeroProgression;

class HeroHelperTest extends AbstractBrowserKitTestCase
{
    use EnablesFullHeroProgression;

    /** @var HeroHelper */
    protected $heroHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableFullHeroProgression();

        $this->heroHelper = $this->app->make(HeroHelper::class);
    }

    public function testUpgradeTypesAreGroupedBySlotLevel(): void
    {
        $types = $this->heroHelper->getUpgradeTypesByLevel('blacksmith');

        $this->assertEquals([1, 2, 4, 6], array_keys($types));
        $this->assertEquals(['doctrine'], $types[1]);
        $this->assertEquals(['item', 'magic'], $types[2]);
    }

    public function testDirectivesDoNotAppearAsSlotTypes(): void
    {
        $types = $this->heroHelper->getUpgradeTypesByLevel('scion');

        foreach ($types as $levelTypes) {
            $this->assertNotContains('directive', $levelTypes);
        }
    }

    public function testLoadoutOptionNamesComeFromTheirDefinition(): void
    {
        $registry = $this->app->make(CombatRegistry::class);

        $this->assertEquals('Backstab', $this->heroHelper->getLoadoutOptionName($registry->loadoutOption('backstab')));
        $this->assertEquals('Shadow Strike', $this->heroHelper->getLoadoutOptionName($registry->loadoutOption('shadow_strike')));
        $this->assertEquals('Swordbreaker Dagger', $this->heroHelper->getLoadoutOptionName($registry->loadoutOption('swordbreaker')));
    }

    public function testLoadoutOptionDescriptionsCoverEffectsAbilitiesAndGear(): void
    {
        $registry = $this->app->make(CombatRegistry::class);

        $this->assertStringContainsString('against a target that was Focused', $this->heroHelper->getLoadoutOptionDescription($registry->loadoutOption('backstab')));
        $this->assertStringContainsString('can\'t be evaded', $this->heroHelper->getLoadoutOptionDescription($registry->loadoutOption('shadow_strike')));
        $this->assertEquals('+2 Attack, -10 Evasion', $this->heroHelper->getLoadoutOptionDescription($registry->loadoutOption('greatsword')));
    }

    public function testLoadoutSummaryNamesTheTechniqueAndAbilityButNotGear(): void
    {
        $hero = new Hero([
            'class' => 'blacksmith',
            'experience' => 5000,
            'loadout' => ['technique' => 'backstab', 'ability' => 'forge', 'gear' => ['greatsword']],
        ]);

        $this->assertEquals('Backstab, Forge', $this->heroHelper->getLoadoutSummary($hero));
    }

    public function testLoadoutSummaryLeavesOutEmptySlotsAndUnusableOptions(): void
    {
        $hero = new Hero(['class' => 'blacksmith', 'experience' => 5000]);
        $this->assertSame('', $this->heroHelper->getLoadoutSummary($hero));

        $hero->loadout = ['technique' => 'backstab', 'ability' => 'shadow_strike'];
        $this->assertEquals('Backstab', $this->heroHelper->getLoadoutSummary($hero));
    }

    public function testLoadoutRequirementsNameTheClassForSignatures(): void
    {
        $registry = $this->app->make(CombatRegistry::class);

        $this->assertEquals('Requires Infiltrator level 5', $this->heroHelper->getLoadoutOptionRequirement($registry->loadoutOption('shadow_strike')));
        $this->assertEquals('Requires level 7', $this->heroHelper->getLoadoutOptionRequirement($registry->loadoutOption('riposte')));
        $this->assertEquals('Requires level 5', $this->heroHelper->getLoadoutOptionRequirement($registry->loadoutOption('whirlwind')), 'Open to every class');
    }

    public function testReviewedAbilitiesAndTechniquesKeepTheirRulesInATooltip(): void
    {
        $registry = $this->app->make(CombatRegistry::class);

        foreach (['fortify', 'caltrops', 'shadow_strike', 'blade_flurry', 'whirlwind', 'volatile_mixture', 'forge', 'combat_analysis', 'mending', 'channeling', 'hardiness', 'backstab', 'feint', 'second_wind', 'restoration', 'shield_bash', 'riposte', 'spellbrand', 'battle_fury'] as $key) {
            $this->assertNotNull($this->heroHelper->getLoadoutOptionRulesTooltip($registry->loadoutOption($key)), "{$key} has been reviewed");
        }

        $this->assertNull($this->heroHelper->getLoadoutOptionRulesTooltip($registry->loadoutOption('greatsword')), 'Gear has no notes');
    }

    public function testLoadoutSlotRequirementsUseTheLowestOptionLevel(): void
    {
        $this->assertEquals('Reach level 3', $this->heroHelper->getLoadoutSlotRequirement(LoadoutSlot::Technique));
        $this->assertEquals('Reach level 5', $this->heroHelper->getLoadoutSlotRequirement(LoadoutSlot::Ability));
        $this->assertEquals('Reach level 3', $this->heroHelper->getLoadoutSlotRequirement(LoadoutSlot::Gear));
    }
}
