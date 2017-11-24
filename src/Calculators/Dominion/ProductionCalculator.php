<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;

class ProductionCalculator
{
    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var PopulationCalculator */
    protected $populationCalculator;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /**
     * ProductionCalculator constructor.
     *
     * @param ImprovementCalculator $improvementCalculator
     * @param PopulationCalculator $populationCalculator
     * @param SpellCalculator $spellCalculator
     */
    public function __construct(ImprovementCalculator $improvementCalculator, PopulationCalculator $populationCalculator, SpellCalculator $spellCalculator)
    {
        $this->improvementCalculator = $improvementCalculator;
        $this->populationCalculator = $populationCalculator;
        $this->spellCalculator = $spellCalculator;
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
        $spellAlchemistFlameAlchemyBonus = 15;
        $platinumPerAlchemy = 45;

        // Peasant Tax
        $platinum += ($this->populationCalculator->getPopulationEmployed($dominion) * $peasantTax);

        // Spell: Alchemist Flame
        if ($this->spellCalculator->isSpellActive($dominion, 'alchemist_flame')) {
            $platinumPerAlchemy += $spellAlchemistFlameAlchemyBonus;
        }

        // Building: Alchemy
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
        $multiplier = 0;

        // Values (percentages)
        $spellMidasTouch = 10;
//        $guardTax = -2;
//        $techBankersForesight = 5;
//        $techTreasureHunt = 12.5;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('platinum_production');

        // Spell: Midas Touch
        $multiplier += $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, 'midas_touch', $spellMidasTouch);

        // Improvement: Science
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'science');

        // Guard Tax
        // todo

        // Tech: Treasure Hunt or Banker's Foresight
        // todo

        return min(1.5, (1 + $multiplier));
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
        $foodPerDock = 35;

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
        $multiplier = 0;

        // Values (percentages)
        $spellGaiasBlessing = 20;
        $spellGaiasWatch = 10;
//        $techFarmersGrowth = 10;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('food_production');

        // Spell: Gaia's Blessing or Gaia's Watch
        $multiplier += $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, [
            'gaias_blessing' => $spellGaiasBlessing,
            'gaias_watch' => $spellGaiasWatch,
        ]);

        // Improvement: Harbor
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'harbor');

        // Tech: Farmer's Growth
        // todo

        // Prestige Bonus
        $multiplier *= (1 + (($dominion->prestige / 250) * 2.5) / 100);
        $multiplier += ((($dominion->prestige / 250) * 2.5) / 100);

        return (1 + $multiplier);
    }

    /**
     * Returns the Dominion's food consumption.
     *
     * Each unit in a Dominion's population eats 0.25 food per hour.
     *
     * Food consumption is modified by Racial Bonus.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getFoodConsumption(Dominion $dominion): float
    {
        $consumption = 0;

        // Values
        $populationConsumption = 0.25;

        // Population Consumption
        $consumption += ($this->populationCalculator->getPopulation($dominion) * $populationConsumption);

        // Racial Bonus
        // todo: getFoodConsumptionRaw & getFoodConsumptionMultiplier?
        $consumption *= (1 + $dominion->race->getPerkMultiplier('food_consumption'));

        return $consumption;
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
        $decay = 0;

        // Values (percentages)
        $foodDecay = 1;

        $decay += ($dominion->resource_food * ($foodDecay / 100));

        return $decay;
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

        // Building: Lumberyard
        $lumber += ($dominion->building_lumberyard * $lumberPerLumberyard);

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
        $multiplier = 0;

        // Values (percentages)
        $spellGaiasBlessing = 10;
        $techProduction = 10;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('lumber_production');

        // Spell: Gaia's Blessing
        $multiplier += $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, 'gaias_blessing', $spellGaiasBlessing);

        // Tech: Fruits of Labor
        // todo

        return (1 + $multiplier);
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
        $decay = 0;

        // Values (percentages)
        $lumberDecay = 1;

        $decay += ($dominion->resource_lumber * ($lumberDecay / 100));

        return $decay;
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
        $manaPerTower = 25;

        // Building: Tower
        $mana += ($dominion->building_tower * $manaPerTower);

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
        $multiplier = 0;

        // Values (percentages)

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('mana_production');

        // Tech: Enchanted Lands
        // todo

        return (1 + $multiplier);
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
        $decay = 0;

        // Values (percentages)
        $manaDecay = 2;

        $decay += ($dominion->resource_mana * ($manaDecay / 100));

        return $decay;
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

        // Dwarf Unit: Miner
        // todo

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
        $multiplier = 0;

        // Values (percentages)
        $spellMinersSight = 20;
        $spellMiningStrength = 10;
//        $techFruitsOfLabor = 20;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('ore_production');

        // Spell: Miner's Sight or Mining Strength
        $multiplier += $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, [
            'miners_sight' => $spellMinersSight,
            'mining_strength' => $spellMiningStrength,
        ]);

        // Tech: Fruits of Labor
        // todo

        return (1 + $multiplier);
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
     * Gems are rpoduced by:
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
        $multiplier = 0;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('gem_production');

        // Tech: Fruits of Labor and Miner's Refining
        // todo

        return (1 + $multiplier);
    }

    //</editor-fold>

    // Tech
    // todo

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
        $multiplier = 0;

        // Improvement: Harbor
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'harbor');

        return (1 + $multiplier);
    }

    //</editor-fold>
}
