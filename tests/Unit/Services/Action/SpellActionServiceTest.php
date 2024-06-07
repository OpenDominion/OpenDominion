<?php

namespace OpenDominion\Tests\Unit\Services\Action;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Calculators\Dominion\OpsCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\RealmWar;
use OpenDominion\Models\Round;
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

        $this->dominion = $this->createDominion($user, $this->round, Race::where('name', 'Dark Elf')->firstOrFail());
        $this->dominion->protection_ticks_remaining = 0;
        $this->dominion->land_plain = 10000;

        $targetUser = $this->createUser();
        $this->target = $this->createDominion($targetUser, $this->round, Race::where('name', 'Human')->firstOrFail());
        $this->target->protection_ticks_remaining = 0;
        $this->target->land_plain = 10000;

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
        $this->dominion->military_wizards = 5000;
        $this->target->military_wizards = 5000;

        // Act
        $this->spellActionService->castSpell($this->dominion, 'insect_swarm', $this->target);

        // Assert
        $this->assertEquals(4950, $this->dominion->military_wizards);
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
        $this->dominion->military_unit3 = 25000;
        $this->target->military_wizards = 5000;

        // Act
        $this->spellActionService->castSpell($this->dominion, 'insect_swarm', $this->target);

        // Assert
        $this->assertEquals(24976, $this->dominion->military_unit3);
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
        $this->assertEquals(52409, $this->target->peasants);

        // Act
        $this->spellActionService->castSpell($this->dominion, 'fireball', $this->target);

        // Assert
        $this->assertEquals(51098, $this->target->peasants);
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
        $this->target->military_wizards = 2550;
        $this->target->peasants = $populationCalculator->getMaxPeasantPopulation($this->target);
        $this->assertEquals(49859, $this->target->peasants);
        $this->assertEquals(0.5, $opsCalculator->getPeasantVulnerablilityModifier($this->target));
        $this->assertEquals(39887, $opsCalculator->getPeasantsProtected($this->target));
        $this->assertEquals(9972, $opsCalculator->getPeasantsUnprotected($this->target));

        // Act
        $this->spellActionService->castSpell($this->dominion, 'fireball', $this->target);

        // Assert
        $this->assertEquals(49360, $this->target->peasants);
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
        $this->target->military_wizards = 700;
        $this->target->building_wizard_guild = 140;
        $this->target->peasants = $populationCalculator->getMaxPeasantPopulation($this->target);
        $this->assertEquals(53144, $this->target->peasants);

        // Act
        $this->spellActionService->castSpell($this->dominion, 'fireball', $this->target);

        // Assert
        $this->assertEquals(52612, $this->target->peasants);
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
        $this->target->peasants = 26204;

        // Act
        $this->expectException(GameException::class);
        $this->spellActionService->castSpell($this->dominion, 'fireball', $this->target);
    }

    public function testCastSpell_Lightning_NoProtection()
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
        $this->target->improvement_science = 1000;
        $this->target->improvement_keep = 100000;
        $this->target->improvement_walls = 50000;
        $this->target->improvement_harbor = 10000;
        $this->target->stat_total_investment = 161000;

        // Act
        $this->spellActionService->castSpell($this->dominion, 'lightning_bolt', $this->target);

        // Assert
        $this->assertEquals(997, $this->target->improvement_science);
        $this->assertEquals(99750, $this->target->improvement_keep);
        $this->assertEquals(49875, $this->target->improvement_walls);
    }

    public function testCastSpell_Lightning_MaxWpaProtection()
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
        $this->target->military_wizards = 11000;
        $this->target->improvement_keep = 100000;
        $this->target->improvement_walls = 50000;
        $this->target->improvement_harbor = 10000;
        $this->target->stat_total_investment = 160000;

        // Act
        $this->spellActionService->castSpell($this->dominion, 'lightning_bolt', $this->target);

        // Assert
        $this->assertEquals(99950, $this->target->improvement_keep);
        $this->assertEquals(49975, $this->target->improvement_walls);
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
}
