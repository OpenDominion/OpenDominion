<?php

namespace OpenDominion\Tests\Feature\Magic;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Calculators\Dominion\OpsCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\Hero;
use OpenDominion\Models\HeroHeroUpgrade;
use OpenDominion\Models\HeroUpgrade;
use OpenDominion\Models\Race;
use OpenDominion\Models\RealmWar;
use OpenDominion\Models\Round;
use OpenDominion\Models\Spell;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Services\Dominion\ValuablesService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

/**
 * Level 8 magic hero upgrades each strengthen a narrow slice of magic:
 * a single spell's effect, duration or cost, or the rate Resolve builds.
 */
class HeroLevelEightUpgradesTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var SpellActionService */
    protected $spellActionService;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var OpsCalculator */
    protected $opsCalculator;

    /** @var Round */
    protected $round;

    /** @var Dominion */
    protected $dominion;

    /** @var Dominion */
    protected $target;

    protected function setUp(): void
    {
        parent::setUp();

        $user = $this->createAndImpersonateUser();
        $this->round = $this->createRound('-4 days midnight');

        $this->dominion = $this->createDominionWithLegacyStats($user, $this->round, Race::where('name', 'Dark Elf')->firstOrFail());
        $this->dominion->land_plain = 8000;
        $this->dominion->resource_mana = 100000;
        $this->dominion->military_wizards = 5000;

        $targetUser = $this->createUser();
        $this->target = $this->createDominionWithLegacyStats($targetUser, $this->round, Race::where('name', 'Human')->firstOrFail());
        $this->target->land_plain = 8000;
        $this->target->military_wizards = 0;

        $this->spellActionService = $this->app->make(SpellActionService::class);
        $this->spellCalculator = $this->app->make(SpellCalculator::class);
        $this->opsCalculator = $this->app->make(OpsCalculator::class);

        global $mockRandomChance;
        $mockRandomChance = true;
    }

    protected function giveHeroUpgrade(string $upgradeKey): void
    {
        $hero = Hero::create([
            'dominion_id' => $this->dominion->id,
            'name' => 'Test Hero',
            'class' => 'alchemist',
            'experience' => 4250,
            'class_data' => [],
        ]);

        HeroHeroUpgrade::create([
            'hero_id' => $hero->id,
            'hero_upgrade_id' => HeroUpgrade::where('key', $upgradeKey)->firstOrFail()->id,
        ]);

        $this->dominion->load('hero.upgrades.perks');
    }

    protected function declareWar(): void
    {
        RealmWar::create([
            'source_realm_id' => $this->dominion->realm_id,
            'target_realm_id' => $this->target->realm_id,
        ]);
    }

    protected function activateMagicWard(Dominion $dominion, int $duration = 24): void
    {
        DominionSpell::create([
            'dominion_id' => $dominion->id,
            'spell_id' => Spell::where('key', 'magic_ward')->firstOrFail()->id,
            'duration' => $duration,
            'cast_by_dominion_id' => $dominion->id,
        ]);

        $dominion->unsetRelation('spells');
    }

    public function testNullificationBreakWardRemovesThreeHours(): void
    {
        $this->giveHeroUpgrade('nullification');
        $this->declareWar();
        $this->activateMagicWard($this->target);

        $result = $this->spellActionService->castSpell($this->dominion, 'break_ward', $this->target);

        $this->assertEquals(21, DominionSpell::where('dominion_id', $this->target->id)->firstOrFail()->duration);
        $this->assertStringContainsString('3 hours of Magic Ward', $result['message']);
    }

    public function testConflagrationFireballDestroysDoubleFood(): void
    {
        $this->giveHeroUpgrade('conflagration');
        $this->declareWar();
        $this->target->peasants = 40000;
        $this->target->resource_food = 100000;

        $this->spellActionService->castSpell($this->dominion, 'fireball', $this->target);

        $peasantsRatio = (40000 - $this->target->peasants) / 40000 / 0.05;
        $foodRatio = (100000 - $this->target->resource_food) / 100000 / 0.02;

        $this->assertGreaterThan(0, $peasantsRatio);
        $this->assertEqualsWithDelta(2, $foodRatio / $peasantsRatio, 0.01);
    }

    public function testFulminationLightningBoltDealsMoreDamageToKeep(): void
    {
        $this->giveHeroUpgrade('fulmination');
        $this->declareWar();
        $this->target->improvement_keep = 100000;
        $this->target->improvement_walls = 100000;
        $this->target->stat_total_investment = 200000;

        $this->spellActionService->castSpell($this->dominion, 'lightning_bolt', $this->target);

        $wallsDamage = 100000 - $this->target->improvement_walls;
        $keepDamage = 100000 - $this->target->improvement_keep;

        $this->assertGreaterThan(0, $wallsDamage);
        $this->assertEqualsWithDelta(1.5, $keepDamage / $wallsDamage, 0.01);
    }

    public function testWithoutUpgradeBlackOpLastsBaseDuration(): void
    {
        $this->spellActionService->castSpell($this->dominion, 'insect_swarm', $this->target);

        $this->assertEquals(8, $this->target->spells()->where('key', 'insect_swarm')->firstOrFail()->pivot->duration);
    }

    public function testMaledictionBlackOpLastsTwoHoursLonger(): void
    {
        $this->giveHeroUpgrade('malediction');

        $this->spellActionService->castSpell($this->dominion, 'insect_swarm', $this->target);

        $this->assertEquals(10, $this->target->spells()->where('key', 'insect_swarm')->firstOrFail()->pivot->duration);
    }

    protected function getSpell(string $spellKey): Spell
    {
        return Spell::where('key', $spellKey)->firstOrFail();
    }

    public function testSuppressionReducesSilenceAndManaBurnStrengthCost(): void
    {
        $this->giveHeroUpgrade('suppression');

        $this->assertEquals(4, $this->spellCalculator->getStrengthCost($this->dominion, $this->getSpell('silence')));
        $this->assertEquals(4, $this->spellCalculator->getStrengthCost($this->dominion, $this->getSpell('mana_burn')));
        $this->assertEquals(5, $this->spellCalculator->getStrengthCost($this->dominion, $this->getSpell('fireball')));
    }

    public function testAugmentationReducesArcaneConduitManaCost(): void
    {
        $arcaneConduit = $this->getSpell('arcane_conduit');
        $fireball = $this->getSpell('fireball');
        $baseConduitCost = $this->spellCalculator->getManaCost($this->dominion, $arcaneConduit);
        $baseFireballCost = $this->spellCalculator->getManaCost($this->dominion, $fireball);

        $this->giveHeroUpgrade('augmentation');

        $this->assertEqualsWithDelta(0.75, $this->spellCalculator->getManaCost($this->dominion, $arcaneConduit) / $baseConduitCost, 0.001);
        $this->assertEquals($baseFireballCost, $this->spellCalculator->getManaCost($this->dominion, $fireball));
    }

    public function testRestorationReducesRevivePeasantsAndRepairCastleManaCost(): void
    {
        $spellKeys = ['revive_peasants', 'repair_castle', 'arcane_conduit'];
        $baseCosts = [];
        foreach ($spellKeys as $spellKey) {
            $baseCosts[$spellKey] = $this->spellCalculator->getManaCost($this->dominion, $this->getSpell($spellKey));
        }

        $this->giveHeroUpgrade('restoration');

        $this->assertEqualsWithDelta(0.75, $this->spellCalculator->getManaCost($this->dominion, $this->getSpell('revive_peasants')) / $baseCosts['revive_peasants'], 0.001);
        $this->assertEqualsWithDelta(0.75, $this->spellCalculator->getManaCost($this->dominion, $this->getSpell('repair_castle')) / $baseCosts['repair_castle'], 0.001);
        $this->assertEquals($baseCosts['arcane_conduit'], $this->spellCalculator->getManaCost($this->dominion, $this->getSpell('arcane_conduit')));
    }

    public function testRetaliationIncreasesResolveGain(): void
    {
        $this->assertEquals(10, $this->opsCalculator->getResolveGain($this->dominion));

        $this->giveHeroUpgrade('retaliation');

        $this->assertEquals(12, $this->opsCalculator->getResolveGain($this->dominion));
        $this->assertEquals(6, $this->opsCalculator->getResolveGain($this->dominion, true));
    }

    public function testRetaliationStacksAdditivelyWithBacklash(): void
    {
        $this->giveHeroUpgrade('retaliation');
        DominionSpell::create([
            'dominion_id' => $this->dominion->id,
            'spell_id' => $this->getSpell('backlash')->id,
            'duration' => 12,
            'cast_by_dominion_id' => $this->dominion->id,
        ]);
        $this->dominion->unsetRelation('spells');

        $this->assertEquals(7, $this->opsCalculator->getResolveGain($this->dominion));
    }

    public function testFortificationExtendsMagicWardDuration(): void
    {
        $this->giveHeroUpgrade('fortification');

        $this->spellActionService->castSpell($this->dominion, 'magic_ward');

        $this->assertEquals(30, DominionSpell::where('dominion_id', $this->dominion->id)->firstOrFail()->duration);
    }

    public function testWithoutFortificationMagicWardLastsBaseDuration(): void
    {
        $this->spellActionService->castSpell($this->dominion, 'magic_ward');

        $this->assertEquals(24, DominionSpell::where('dominion_id', $this->dominion->id)->firstOrFail()->duration);
    }

    public function testPenetrationHalvesMagicWardDamageReduction(): void
    {
        $unwardedMultiplier = $this->opsCalculator->getSpellDamageMultiplier($this->target, 'fireball', $this->dominion);
        $this->activateMagicWard($this->target);
        $wardedMultiplier = $this->opsCalculator->getSpellDamageMultiplier($this->target, 'fireball', $this->dominion);

        $this->giveHeroUpgrade('penetration');

        $this->assertEqualsWithDelta($wardedMultiplier + 0.25, $this->opsCalculator->getSpellDamageMultiplier($this->target, 'fireball', $this->dominion), 0.0001);

        $this->target->spells()->detach();
        $this->target->unsetRelation('spells');
        $this->assertEqualsWithDelta($unwardedMultiplier, $this->opsCalculator->getSpellDamageMultiplier($this->target, 'fireball', $this->dominion), 0.0001);
    }

    protected function giveTargetHeroUpgrade(string $upgradeKey): void
    {
        $hero = Hero::create([
            'dominion_id' => $this->target->id,
            'name' => 'Target Hero',
            'class' => 'alchemist',
            'experience' => 4250,
            'class_data' => [],
        ]);

        HeroHeroUpgrade::create([
            'hero_id' => $hero->id,
            'hero_upgrade_id' => HeroUpgrade::where('key', $upgradeKey)->firstOrFail()->id,
        ]);

        $this->target->load('hero.upgrades.perks');
    }

    public function testAbsorptionGrantsManaFromWarSpellsWhileWarded(): void
    {
        $this->giveTargetHeroUpgrade('absorption');
        $this->declareWar();
        $this->activateMagicWard($this->target);
        $this->target->peasants = 40000;
        $this->target->resource_mana = 10000;
        $manaCost = $this->spellCalculator->getManaCost($this->dominion, $this->getSpell('fireball'));

        $this->spellActionService->castSpell($this->dominion, 'fireball', $this->target);

        $this->assertEquals(10000 + rfloor($manaCost * 0.05), $this->target->resource_mana);
    }

    public function testAbsorptionGrantsNothingWithoutMagicWard(): void
    {
        $this->giveTargetHeroUpgrade('absorption');
        $this->declareWar();
        $this->target->peasants = 40000;
        $this->target->resource_mana = 10000;

        $this->spellActionService->castSpell($this->dominion, 'fireball', $this->target);

        $this->assertEquals(10000, $this->target->resource_mana);
    }

    public function testPerceptionDoublesInfoSpellValuablesChance(): void
    {
        $valuablesService = $this->app->make(ValuablesService::class);

        $this->giveHeroUpgrade('perception');

        $this->assertEqualsWithDelta(0.02, $valuablesService->getPassiveDiscoveryChance($this->dominion, 0, 'wizards'), 0.00001);
        $this->assertEqualsWithDelta(0.01, $valuablesService->getPassiveDiscoveryChance($this->dominion, 0, 'spies'), 0.00001);
        $this->assertEqualsWithDelta(0.80, $valuablesService->getPassiveDiscoveryChance($this->dominion, 5000, 'wizards'), 0.00001);
    }
}
