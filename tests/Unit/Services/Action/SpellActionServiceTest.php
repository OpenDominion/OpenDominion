<?php

namespace OpenDominion\Tests\Unit\Services\Action;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Calculators\Dominion\OpsCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\Race;
use OpenDominion\Models\RealmWar;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundWonder;
use OpenDominion\Models\Spell;
use OpenDominion\Models\Wonder;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class SpellActionServiceTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var SpellActionService */
    protected $spellActionService;

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
        $this->round = $this->createRound('-3 days midnight');

        $this->dominion = $this->createDominionWithLegacyStats($user, $this->round, Race::where('name', 'Dark Elf')->firstOrFail());
        $this->dominion->land_plain = 8000;

        $targetUser = $this->createUser();
        $this->target = $this->createDominionWithLegacyStats($targetUser, $this->round, Race::where('name', 'Human')->firstOrFail());
        $this->target->land_plain = 8000;

        $this->spellActionService = $this->app->make(SpellActionService::class);

        global $mockRandomChance;
        $mockRandomChance = false;
    }

    public function testCastSpell_Info_NoLosses()
    {
        // Arrange
        $this->dominion->resource_mana = 100000;
        $this->dominion->military_wizards = 5000;
        $this->target->military_wizards = 5000;

        // Act
        $this->spellActionService->castSpell($this->dominion, 'clear_sight', $this->target);

        // Assert
        $this->assertEquals(5000, $this->dominion->military_wizards);
    }

    public function testCastSpell_SameWpa_LoseOnePercent()
    {
        // Arrange
        $this->dominion->resource_mana = 100000;
        $this->dominion->military_wizards = 4000;
        $this->target->military_wizards = 5000;

        // Act
        $this->spellActionService->castSpell($this->dominion, 'insect_swarm', $this->target);

        // Assert
        $this->assertEquals(3950, $this->dominion->military_wizards);
    }

    public function testCastSpell_MuchLowerWpa_LoseMaxOnePointFivePercent()
    {
        // Arrange
        $this->dominion->resource_mana = 100000;
        $this->dominion->military_wizards = 5000;
        $this->target->military_wizards = 50000;

        // Act
        $this->spellActionService->castSpell($this->dominion, 'insect_swarm', $this->target);

        // Assert
        $this->assertEquals(4925, $this->dominion->military_wizards);
    }

    public function testCastSpell_MuchHigherWpa_LoseHalfPercent()
    {
        // Arrange
        $this->dominion->resource_mana = 100000;
        $this->dominion->military_wizards = 5000;
        $this->target->military_wizards = 500;

        // Act
        $this->spellActionService->castSpell($this->dominion, 'insect_swarm', $this->target);

        // Assert
        $this->assertEquals(4975, $this->dominion->military_wizards);
    }

    public function testCastSpell_SameWpa_LoseMilitary()
    {
        // Arrange
        $this->dominion->resource_mana = 100000;
        $this->dominion->military_unit3 = 16000;
        $this->target->military_wizards = 3000;

        // Act
        $this->spellActionService->castSpell($this->dominion, 'insect_swarm', $this->target);

        // Assert
        $this->assertEquals(15986, $this->dominion->military_unit3);
    }

    public function testCastSpell_Fireball_NoProtection()
    {
        global $mockRandomChance;
        $mockRandomChance = true;
        $populationCalculator = app(PopulationCalculator::class);

        // Arrange
        RealmWar::create([
            'source_realm_id' => $this->dominion->realm_id,
            'target_realm_id' => $this->target->realm_id
        ]);
        $this->dominion->resource_mana = 100000;
        $this->dominion->military_wizards = 5000;
        $this->target->military_wizards = 0;
        $this->target->peasants = $populationCalculator->getMaxPeasantPopulation($this->target);
        $this->assertEquals(42519, $this->target->peasants);

        // Act
        $this->spellActionService->castSpell($this->dominion, 'fireball', $this->target);

        // Assert
        $this->assertEquals(41456, $this->target->peasants);
    }

    public function testCastSpell_Fireball_MaxWizardProtection()
    {
        global $mockRandomChance;
        $mockRandomChance = true;
        $opsCalculator = app(OpsCalculator::class);
        $populationCalculator = app(PopulationCalculator::class);

        // Arrange
        RealmWar::create([
            'source_realm_id' => $this->dominion->realm_id,
            'target_realm_id' => $this->target->realm_id
        ]);
        $this->dominion->resource_mana = 100000;
        $this->dominion->military_wizards = 5000;
        $this->target->military_wizards = 3300;
        $this->target->peasants = $populationCalculator->getMaxPeasantPopulation($this->target);
        $this->assertEquals(39219, $this->target->peasants);
        $this->assertEquals(0.5, $opsCalculator->getPeasantVulnerablilityModifier($this->target));
        // $this->assertEquals(39287, $opsCalculator->getPeasantsProtected($this->target));
        // $this->assertEquals(9822, $opsCalculator->getPeasantsUnprotected($this->target));

        // Act
        $this->spellActionService->castSpell($this->dominion, 'fireball', $this->target);

        // Assert
        $this->assertEquals(38826, $this->target->peasants);
    }

    public function testCastSpell_Fireball_MaxWizardGuildProtection()
    {
        global $mockRandomChance;
        $mockRandomChance = true;
        $populationCalculator = app(PopulationCalculator::class);

        // Arrange
        RealmWar::create([
            'source_realm_id' => $this->dominion->realm_id,
            'target_realm_id' => $this->target->realm_id
        ]);
        $this->dominion->resource_mana = 100000;
        $this->dominion->military_wizards = 5000;
        $this->target->military_wizards = 900;
        $this->target->building_wizard_guild = 180;
        $this->target->peasants = $populationCalculator->getMaxPeasantPopulation($this->target);
        $this->assertEquals(43464, $this->target->peasants);

        // Act
        $this->spellActionService->castSpell($this->dominion, 'fireball', $this->target);

        // Assert
        $this->assertEquals(43029, $this->target->peasants);
    }

    public function testCastSpell_Fireball_DamageCap()
    {
        global $mockRandomChance;
        $mockRandomChance = true;

        // Arrange
        RealmWar::create([
            'source_realm_id' => $this->dominion->realm_id,
            'target_realm_id' => $this->target->realm_id
        ]);
        $this->dominion->resource_mana = 100000;
        $this->dominion->military_wizards = 5000;
        $this->target->military_wizards = 0;
        $this->target->peasants = 21260;

        // Act
        $this->expectException(GameException::class);
        $this->spellActionService->castSpell($this->dominion, 'fireball', $this->target);
    }

    public function testCastSpell_Lightning_NoProtection()
    {
        global $mockRandomChance;
        $mockRandomChance = true;
        $opsCalculator = app(OpsCalculator::class);
        $populationCalculator = app(PopulationCalculator::class);

        // Arrange
        RealmWar::create([
            'source_realm_id' => $this->dominion->realm_id,
            'target_realm_id' => $this->target->realm_id
        ]);
        $this->dominion->resource_mana = 100000;
        $this->dominion->military_wizards = 5000;
        $this->target->military_wizards = 0;
        $this->target->improvement_science = 10000;
        $this->target->improvement_keep = 100000;
        $this->target->improvement_walls = 50000;
        $this->target->improvement_harbor = 10000;
        $this->target->stat_total_investment = 170000;

        // Act
        $this->spellActionService->castSpell($this->dominion, 'lightning_bolt', $this->target);

        // Assert
        $this->assertEquals(1, $opsCalculator->getSpellDamageMultiplier($this->target, 'lightning_bolt', $this->dominion));
        $this->assertEquals(9980, $this->target->improvement_science);
        $this->assertEquals(99800, $this->target->improvement_keep);
        $this->assertEquals(49900, $this->target->improvement_walls);
    }

    public function testCastSpell_Lightning_HalfProtection()
    {
        global $mockRandomChance;
        $mockRandomChance = true;
        $opsCalculator = app(OpsCalculator::class);
        $populationCalculator = app(PopulationCalculator::class);

        // Arrange
        RealmWar::create([
            'source_realm_id' => $this->dominion->realm_id,
            'target_realm_id' => $this->target->realm_id
        ]);
        $this->dominion->resource_mana = 100000;
        $this->dominion->military_wizards = 5000;
        $this->target->building_wizard_guild = 525;
        $this->target->improvement_keep = 100000;
        $this->target->improvement_walls = 50000;
        $this->target->improvement_harbor = 10000;
        $this->target->stat_total_investment = 160000;

        // Act
        $this->spellActionService->castSpell($this->dominion, 'lightning_bolt', $this->target);

        // Assert
        $this->assertEquals(0.5, $opsCalculator->getSpellDamageMultiplier($this->target, 'lightning_bolt', $this->dominion));
        $this->assertEquals(99900, $this->target->improvement_keep);
        $this->assertEquals(49950, $this->target->improvement_walls);
    }

    public function testCastSpell_Lightning_MaxProtection()
    {
        global $mockRandomChance;
        $mockRandomChance = true;
        $opsCalculator = app(OpsCalculator::class);
        $populationCalculator = app(PopulationCalculator::class);

        // Arrange
        RealmWar::create([
            'source_realm_id' => $this->dominion->realm_id,
            'target_realm_id' => $this->target->realm_id
        ]);
        $this->dominion->resource_mana = 100000;
        $this->dominion->military_wizards = 5000;
        $this->target->building_wizard_guild = 525;
        $this->target->improvement_keep = 100000;
        $this->target->improvement_walls = 50000;
        $this->target->improvement_spires = 100000000;
        $this->target->improvement_harbor = 10000;
        $this->target->stat_total_investment = 100160000;

        // Act
        $this->spellActionService->castSpell($this->dominion, 'lightning_bolt', $this->target);

        // Assert
        $this->assertEquals(0.2, $opsCalculator->getSpellDamageMultiplier($this->target, 'lightning_bolt', $this->dominion));
        $this->assertEquals(99960, $this->target->improvement_keep);
        $this->assertEquals(49980, $this->target->improvement_walls);
    }

    public function testCastSpell_Lightning_DamageCap()
    {
        global $mockRandomChance;
        $mockRandomChance = true;
        $populationCalculator = app(PopulationCalculator::class);

        // Arrange
        RealmWar::create([
            'source_realm_id' => $this->dominion->realm_id,
            'target_realm_id' => $this->target->realm_id
        ]);
        $this->dominion->resource_mana = 100000;
        $this->dominion->military_wizards = 5000;
        $this->target->military_wizards = 0;
        $this->target->improvement_keep = 100000;
        $this->target->stat_total_investment = 133000;

        // Act
        $this->expectException(GameException::class);
        $this->spellActionService->castSpell($this->dominion, 'lightning_bolt', $this->target);
    }

    public function testSpellModifiers()
    {
        global $mockRandomChance;
        $mockRandomChance = true;
        $opsCalculator = app(OpsCalculator::class);
        $populationCalculator = app(PopulationCalculator::class);

        // Arrange
        RealmWar::create([
            'source_realm_id' => $this->dominion->realm_id,
            'target_realm_id' => $this->target->realm_id
        ]);
        $this->dominion->resource_mana = 100000;
        $this->dominion->military_wizards = 5000;
        $this->target->military_wizards = 0;
        $this->target->peasants = $populationCalculator->getMaxPeasantPopulation($this->target);
        $this->assertEquals(42519, $this->target->peasants);

        // Standard Fireball
        $this->spellActionService->castSpell($this->dominion, 'fireball', $this->target);
        $this->assertEquals(41456, $this->target->peasants);

        // Burning Fireball
        $burningSpell = Spell::where('key', 'burning')->first();
        DominionSpell::create([
            'dominion_id' => $this->target->id,
            'spell_id' => $burningSpell->id,
            'duration' => 20
        ]);
        $this->target->refresh();
        $this->spellActionService->castSpell($this->dominion, 'fireball', $this->target);
        $this->assertEquals(39330, $this->target->peasants);

        // Wizard Academy + Burning Fireball
        $wizardAcademy = Wonder::where('key', 'wizard_academy')->first();
        RoundWonder::create([
            'round_id' => $this->target->round_id,
            'wonder_id' => $wizardAcademy->id,
            'realm_id' => $this->target->realm_id,
            'power' => 250000
        ]);
        $this->target->refresh();
        $this->spellActionService->castSpell($this->dominion, 'fireball', $this->target);
        $this->assertEquals(38267, $this->target->peasants);
    }
}
