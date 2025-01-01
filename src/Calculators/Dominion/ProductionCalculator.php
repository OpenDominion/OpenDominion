<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\GuardMembershipService;

class ProductionCalculator
{
    /** @var HeroCalculator */
    protected $heroCalculator;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var PopulationCalculator */
    protected $populationCalculator;

    /** @var PrestigeCalculator */
    private $prestigeCalculator;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var GuardMembershipService */
    private $guardMembershipService;

    /**
     * ProductionCalculator constructor.
     *
     * @param HeroCalculator $heroCalculator
     * @param ImprovementCalculator $improvementCalculator
     * @param LandCalculator $landCalculator
     * @param PopulationCalculator $populationCalculator
     * @param PrestigeCalculator $prestigeCalculator
     * @param SpellCalculator $spellCalculator
     * @param GuardMembershipService $guardMembershipService
     */
    public function __construct(
        HeroCalculator $heroCalculator,
        ImprovementCalculator $improvementCalculator,
        LandCalculator $landCalculator,
        PopulationCalculator $populationCalculator,
        PrestigeCalculator $prestigeCalculator,
        SpellCalculator $spellCalculator,
        GuardMembershipService $guardMembershipService
    )
    {
        $this->heroCalculator = $heroCalculator;
        $this->improvementCalculator = $improvementCalculator;
        $this->landCalculator = $landCalculator;
        $this->populationCalculator = $populationCalculator;
        $this->prestigeCalculator = $prestigeCalculator;
        $this->spellCalculator = $spellCalculator;
        $this->guardMembershipService = $guardMembershipService;
    }

    //<editor-fold desc="Platinum">

    /**
     * Returns the Dominion's platinum production.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPlatinumProduction(Dominion $dominion): int
    {
        return floor($this->getPlatinumProductionRaw($dominion) * $this->getPlatinumProductionMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw platinum production.
     *
     * Platinum is produced by:
     * - Employed Peasants (2.7 per)
     * - Building: Alchemy (45 per, or 60 with Alchemist Flame racial spell active)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getPlatinumProductionRaw(Dominion $dominion): float
    {
        $platinum = 0;

        // Values
        $peasantTax = 2.7;
        $platinumPerAlchemy = 45;

        // Peasant Tax
        $platinum += ($this->populationCalculator->getPopulationEmployed($dominion) * $peasantTax);

        // Building: Alchemy
        $platinumPerAlchemy += $dominion->getSpellPerkValue('platinum_production_raw');
        $platinum += ($dominion->building_alchemy * $platinumPerAlchemy);

        return $platinum;
    }

    /**
     * Returns the Dominion's platinum production multiplier.
     *
     * Platinum production is modified by:
     * - Racial Bonus
     * - Spell: Midas Touch (+10%)
     * - Improvement: Science
     * - Guard Tax (-2%)
     * - Tech: Treasure Hunt (+12.5%) or Banker's Foresight (+5%)
     *
     * Platinum production multiplier is capped at +50%.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getPlatinumProductionMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        // Values
        $guardTax = 2;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('platinum_production');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('platinum_production');
        $guardTax += $dominion->getTechPerkValue('guard_tax');

        // Wonders
        $multiplier += $dominion->getWonderPerkMultiplier('platinum_production');
        $guardTax += $dominion->getWonderPerkValue('guard_tax');

        // Spells
        $multiplier += $dominion->getSpellPerkMultiplier('platinum_production');

        // Improvement: Science
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'science');

        // Heroes
        $multiplier += $this->heroCalculator->getHeroPerkMultiplier($dominion, 'platinum_production');

        // Guard Tax
        if ($this->guardMembershipService->isGuardMember($dominion)) {
            $multiplier -= ($guardTax / 100);
        }

        return $multiplier;
    }

    //</editor-fold>

    //<editor-fold desc="Food">

    /**
     * Returns the Dominion's food production.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getFoodProduction(Dominion $dominion): int
    {
        return floor($this->getFoodProductionRaw($dominion) * $this->getFoodProductionMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw food production.
     *
     * Food is produced by:
     * - Building: Farm (80 per)
     * - Building: Dock (35 per)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getFoodProductionRaw(Dominion $dominion): float
    {
        $food = 0;

        // Values
        $foodPerFarm = 80;
        $foodPerDock = 40;

        // Techs
        $foodPerDock += $dominion->getTechPerkValue('food_production_docks');

        // Building: Farm
        $food += ($dominion->building_farm * $foodPerFarm);

        // Building: Dock
        $food += ($dominion->building_dock * $foodPerDock);

        return $food;
    }

    /**
     * Returns the Dominion's food production multiplier.
     *
     * Food production is modified by:
     * - Racial Bonus
     * - Spell: Gaia's Blessing (+20%) or Gaia's Watch (+10%)
     * - Improvement: Harbor
     * - Tech: Farmer's Growth (+10%)
     * - Prestige (+1% per 100 prestige, multiplicative)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getFoodProductionMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('food_production');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('food_production');

        // Wonders
        $multiplier += $dominion->getWonderPerkMultiplier('food_production');

        // Spells
        $multiplier += $this->spellCalculator->resolveSpellPerk($dominion, 'food_production') / 100;

        // Improvement: Harbor
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'harbor');

        // Heroes
        $multiplier += $this->heroCalculator->getHeroPerkMultiplier($dominion, 'food_production');

        // Prestige Bonus
        $multiplier += ($this->prestigeCalculator->getPrestigeMultiplier($dominion) * (1 + $dominion->getTechPerkMultiplier('food_production_prestige')));

        return $multiplier;
    }

    /**
     * Returns the Dominion's food consumption.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getFoodConsumption(Dominion $dominion): float
    {
        return floor($this->getFoodConsumptionRaw($dominion) * $this->getFoodConsumptionMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw food consumption.
     *
     * Each unit in a Dominion's population eats 0.25 food per hour.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getFoodConsumptionRaw(Dominion $dominion): float
    {
        $consumption = 0;

        // Values
        $populationConsumption = 0.25;

        // Population Consumption
        $consumption = ($this->populationCalculator->getPopulation($dominion) * $populationConsumption);

        return $consumption;
    }

    /**
     * Returns the Dominion's food consumption multiplier.
     *
     * Food consumption is modified by:
     * - Racial Bonus
     * - Techs
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getFoodConsumptionMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('food_consumption');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('food_consumption');

        return $multiplier;
    }

    /**
     * Returns the Dominion's food decay.
     *
     * Food decays 1% per hour.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getFoodDecay(Dominion $dominion): float
    {
        $multiplier = $this->getFoodDecayMultiplier($dominion);

        // Values
        $foodDecay = 1;

        return round($dominion->resource_food * ($foodDecay * $multiplier / 100));
    }

    /**
     * Returns the Dominion's food decay multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getFoodDecayMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        // Spells
        $multiplier += $dominion->getSpellPerkMultiplier('food_decay');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('food_decay');

        return $multiplier;
    }

    /**
     * Returns the Dominion's net food change.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getFoodNetChange(Dominion $dominion): int
    {
        return round($this->getFoodProduction($dominion) - $this->getFoodConsumption($dominion) - $this->getFoodDecay($dominion));
    }

    //</editor-fold>

    //<editor-fold desc="Lumber">

    /**
     * Returns the Dominion's lumber production.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getLumberProduction(Dominion $dominion): int
    {
        return floor($this->getLumberProductionRaw($dominion) * $this->getLumberProductionMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw lumber production.
     *
     * Lumber is produced by:
     * - Building: Lumberyard (50 per)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getLumberProductionRaw(Dominion $dominion): float
    {
        $lumber = 0;

        // Values
        $lumberPerLumberyard = 50;
        $lumberPerForestHaven = 25;

        // Building: Lumberyard
        $lumber += ($dominion->building_lumberyard * $lumberPerLumberyard);

        // Building: Forest Haven
        $lumber += ($dominion->building_forest_haven * $lumberPerForestHaven);

        return $lumber;
    }

    /**
     * Returns the Dominion's lumber production multiplier.
     *
     * Lumber production is modified by:
     * - Racial Bonus
     * - Spell: Gaia's Blessing (+10%)
     * - Tech: Fruits of Labor (20%)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getLumberProductionMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('lumber_production');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('lumber_production');

        // Wonders
        $multiplier += $dominion->getWonderPerkMultiplier('lumber_production');

        // Spells
        $multiplier += $dominion->getSpellPerkMultiplier('lumber_production');

        return $multiplier;
    }

    /**
     * Returns the Dominion's lumber decay.
     *
     * Lumber decays 1% per hour.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getLumberDecay(Dominion $dominion): float
    {
        $multiplier = $this->getLumberDecayMultiplier($dominion);

        // Values
        $lumberDecay = 1;

        return round($dominion->resource_lumber * ($lumberDecay * $multiplier / 100));
    }

    /**
     * Returns the Dominion's lumber decay multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getLumberDecayMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('lumber_decay');

        // Spells
        $multiplier += $dominion->getSpellPerkMultiplier('lumber_decay');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('lumber_decay');

        return $multiplier;
    }

    /**
     * Returns the Dominion's net lumber change.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getLumberNetChange(Dominion $dominion): int
    {
        return round($this->getLumberProduction($dominion) - $this->getLumberDecay($dominion));
    }

    //</editor-fold>

    //<editor-fold desc="Mana">

    /**
     * Returns the Dominion's mana production.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getManaProduction(Dominion $dominion): int
    {
        return floor($this->getManaProductionRaw($dominion) * $this->getManaProductionMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw mana production.
     *
     * Mana is produced by:
     * - Building: Tower (25 per)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getManaProductionRaw(Dominion $dominion): float
    {
        $mana = 0;

        // Values
        $manaPerWizardGuild = 5;
        $manaPerTower = 25;

        // Spells
        $manaPerWizardGuild += $this->spellCalculator->resolveSpellPerk($dominion, 'wizard_guild_mana_production_raw');

        // Buildings: Tower + Wizard Guild
        $mana += ($dominion->building_tower * $manaPerTower);
        $mana += ($dominion->building_wizard_guild * $manaPerWizardGuild);

        // Techs
        $mana += ($dominion->building_tower * $dominion->getTechPerkValue('mana_production_raw'));

        $wartimeManaProduction = $dominion->getTechPerkValue('wartime_mana_production_raw');
        if ($wartimeManaProduction > 0) {
            $warCount = min(2, $dominion->realm->warsIncoming()->active()->count() + $dominion->realm->warsOutgoing()->active()->count());
            $mana += ($dominion->building_tower * $wartimeManaProduction * $warCount);
        }

        return $mana;
    }

    /**
     * Returns the Dominion's mana production multiplier.
     *
     * Mana production is modified by:
     * - Racial Bonus
     * - Tech: Enchanted Lands (+15%)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getManaProductionMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('mana_production');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('mana_production');

        // Wonders
        $multiplier += $dominion->getWonderPerkMultiplier('mana_production');

        // Improvement: Spires
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'spires');

        // Spells
        $multiplier += $this->spellCalculator->resolveSpellPerk($dominion, 'mana_production') / 100;

        return $multiplier;
    }

    /**
     * Returns the Dominion's mana decay.
     *
     * Mana decays 2% per hour.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getManaDecay(Dominion $dominion): float
    {
        $multiplier = $this->getManaDecayMultiplier($dominion);

        // Values
        $manaDecay = 2;

        return round($dominion->resource_mana * ($manaDecay * $multiplier / 100));
    }

    /**
     * Returns the Dominion's mana decay multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getManaDecayMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        // Spells
        $multiplier += $this->spellCalculator->resolveSpellPerk($dominion, 'mana_decay') / 100;

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('mana_decay');

        return $multiplier;
    }

    /**
     * Returns the Dominion's net mana change.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getManaNetChange(Dominion $dominion): int
    {
        return round($this->getManaProduction($dominion) - $this->getManaDecay($dominion));
    }

    //</editor-fold>

    //<editor-fold desc="Ore">

    /**
     * Returns the Dominion's ore production.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getOreProduction(Dominion $dominion): int
    {
        return floor($this->getOreProductionRaw($dominion) * $this->getOreProductionMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw ore production.
     *
     * Ore is produced by:
     * - Building: Ore Mine (60 per)
     * - Dwarf Unit: Miner (0.5 per)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOreProductionRaw(Dominion $dominion): float
    {
        $ore = 0;

        // Values
        $orePerOreMine = 60;

        // Building: Ore Mine
        $ore += ($dominion->building_ore_mine * $orePerOreMine);

        // Unit Perk Production Bonus (Dwarf Unit: Miner)
        $ore += $dominion->getUnitPerkProductionBonus('ore_production');

        return $ore;
    }

    /**
     * Returns the Dominion's ore production multiplier.
     *
     * Ore production is modified by:
     * - Racial Bonus
     * - Spell: Miner's Sight (+20%) or Mining Strength (+10%)
     * - Tech: Fruits of Labor (+20%)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOreProductionMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('ore_production');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('ore_production');

        // Wonders
        $multiplier += $dominion->getWonderPerkMultiplier('ore_production');

        // Spells
        $multiplier += $this->spellCalculator->resolveSpellPerk($dominion, 'ore_production') / 100;

        return $multiplier;
    }

    //</editor-fold>

    //<editor-fold desc="Gems">

    /**
     * Returns the Dominion's gem production.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getGemProduction(Dominion $dominion): int
    {
        return floor($this->getGemProductionRaw($dominion) * $this->getGemProductionMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw gem production.
     *
     * Gems are produced by:
     * - Building: Diamond Mine (15 per)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getGemProductionRaw(Dominion $dominion): float
    {
        $gems = 0;

        // Values
        $gemsPerDiamondMine = 15;

        // Building: Diamond Mine
        $gems += ($dominion->building_diamond_mine * $gemsPerDiamondMine);

        return $gems;
    }

    /**
     * Returns the Dominion's gem production multiplier.
     *
     * Gem production is modified by:
     * - Racial Bonus
     * - Tech: Fruits of Labor (+10%) and Miner's Refining (+5%)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getGemProductionMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('gem_production');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('gem_production');

        // Wonders
        $multiplier += $dominion->getWonderPerkMultiplier('gem_production');

        // Spells
        $multiplier += $this->spellCalculator->resolveSpellPerk($dominion, 'gem_production') / 100;

        // Heroes
        $multiplier += $this->heroCalculator->getHeroPerkMultiplier($dominion, 'gem_production');

        return $multiplier;
    }

    //</editor-fold>

    //<editor-fold desc="Tech">

    /**
     * Returns the Dominion's research point production.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getTechProduction(Dominion $dominion): int
    {
        return floor($this->getTechProductionRaw($dominion) * $this->getTechProductionMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw tech production.
     *
     * Research points are produced by:
     * - Building: School
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getTechProductionRaw(Dominion $dominion): float
    {
        $tech = 0;
        $totalLand = $this->landCalculator->getTotalLand($dominion);

        // Values
        $schoolPercentageCap = 50;

        // Building: School
        $schoolPercentage = min(
            $schoolPercentageCap / 100,
            $dominion->building_school / $totalLand
        );
        $tech += min($dominion->building_school, floor($totalLand / 2)) * (1 - $schoolPercentage);

        // Wonders
        $tech += $dominion->getWonderPerkValue('tech_production_raw');

        return $tech;
    }

    /**
     * Returns the Dominion's research point production multiplier.
     *
     * Research point production is modified by:
     * - Racial Bonus
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getTechProductionMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('tech_production');

        // Wonders
        $multiplier += $dominion->getWonderPerkMultiplier('tech_production');

        // Heroes
        if ($dominion->hero !== null) {
            $multiplier += $dominion->hero->getPerkMultiplier('tech_production');
        }

        return $multiplier;
    }

    //</editor-fold>

    //<editor-fold desc="Boats">

    /**
     * Returns the Dominion's boat production per hour.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getBoatProduction(Dominion $dominion): float
    {
        return ($this->getBoatProductionRaw($dominion) * $this->getBoatProductionMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw boat production per hour.
     *
     * Boats are produced by:
     * - Building: Dock (20 per)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getBoatProductionRaw(Dominion $dominion): float
    {
        $boats = 0;

        // Values
        $docksPerBoatPerTick = 20;

        $boats += ($dominion->building_dock / $docksPerBoatPerTick);

        return $boats;
    }

    /**
     * Returns the Dominions's boat production multiplier.
     *
     * Boat production is modified by:
     * - Improvement: Harbor
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getBoatProductionMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('boat_production');

        // Spells
        $multiplier += $this->spellCalculator->resolveSpellPerk($dominion, 'boat_production') / 100;

        // Improvement: Harbor
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'harbor', true);

        return $multiplier;
    }

    //</editor-fold>
}
