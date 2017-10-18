<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;

class ProductionCalculator
{
    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var PopulationCalculator */
    protected $populationCalculator;

    /**
     * ProductionCalculator constructor.
     *
     * @param ImprovementCalculator $improvementCalculator
     * @param PopulationCalculator $populationCalculator
     */
    public function __construct(ImprovementCalculator $improvementCalculator, PopulationCalculator $populationCalculator)
    {
        $this->improvementCalculator = $improvementCalculator;
        $this->populationCalculator = $populationCalculator;
    }

    // Platinum

    /**
     * Returns the Dominion's platinum production.
     *
     * @return int
     */
    public function getPlatinumProduction(Dominion $dominion): int
    {
        return (int)floor($this->getPlatinumProductionRaw($dominion) * $this->getPlatinumProductionMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw platinum production.
     *
     * @return float
     */
    public function getPlatinumProductionRaw(Dominion $dominion): float
    {
        $platinum = 0;

        // Values
        $peasantTax = 2.7;
        $spellAlchemistFlameBonus = 15;
        $platinumPerAlchemy = 45;

        // Peasant Tax
        $platinum += ($this->populationCalculator->getPopulationEmployed($dominion) * $peasantTax);
//        $platinum += (($this->dominion->peasants * $peasantTax) * ($this->populationCalculator->getEmploymentPercentage() / 100));

        // Spell: Alchemist Flame
        // todo

        // Alchemies
        $platinum += ($dominion->building_alchemy * $platinumPerAlchemy);

        /*
        todo: cleanup
        = Population!C3 * Constants!$B$58 * Population!I3 + (Construction!AT4+Construction!BR4)*(Constants!$B$4+(Magic!AI3>0)*Constants!$F$83)
        */

        return (float)$platinum;
    }

    /**
     * Returns the Dominion's platinum production multiplier.
     *
     * @return float
     */
    public function getPlatinumProductionMultiplier(Dominion $dominion): float
    {
        $multiplier = 0.0;

        // Values (percentages)
        $spellMidasTouch = 10;
        $guardTax = -2;
        $techProduction = 5;
        $techTreasureHunt = 12.5;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('platinum_production');

        // Spell: Midas Touch
        // todo

        // Improvement: Science
        $multiplier += $this->improvementCalculator->getImprovementMultiplier($dominion, 'science');

        // Guard Tax
        // todo

        // Tech: Production or Treasure Hunt
        // todo

        return (float)min(1.5, (1 + $multiplier));
    }

    // Food

    /**
     * Returns the Dominion's food production.
     *
     * @return int
     */
    public function getFoodProduction(Dominion $dominion): int
    {
        return (int)floor($this->getFoodProductionRaw($dominion) * $this->getFoodProductionMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw food production.
     *
     * @return float
     */
    public function getFoodProductionRaw(Dominion $dominion): float
    {
        $food = 0;

        // Values
        $foodPerFarm = 80;
        $foodPerDock = 35;

        // Farms
        $food += ($dominion->building_farm * $foodPerFarm);

        // Docks
        $food += ($dominion->building_dock * $foodPerDock);

        return (float)$food;
    }

    /**
     * Returns the Dominion's food production multiplier.
     *
     * @return float
     */
    public function getFoodProductionMultiplier(Dominion $dominion): float
    {
        $multiplier = 0.0;

        // Values (percentages)
        $spellGaiasBlessing = 20;
        $spellGaiasWatch = 10;
        $techProduction = 10;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('food_production');

        // Spell: Gaia's Blessing
        // todo

        // Spell: Gaia's Watch
        // todo

        // Improvement: Irrigation
        $multiplier += $this->improvementCalculator->getImprovementMultiplier($dominion, 'irrigation');

        // Tech: Production
        // todo

        // Prestige bonus multiplier
        $multiplier *= (1 + (($dominion->prestige / 250) * 2.5) / 100);
        $multiplier += ((($dominion->prestige / 250) * 2.5) / 100);

        return (float)(1 + $multiplier);
    }

    /**
     * Returns the Dominion's food consumption.
     *
     * @return float
     */
    public function getFoodConsumption(Dominion $dominion): float
    {
        $consumption = 0;

        // Values
        $populationConsumption = 0.25;

        // Population consumption
        $consumption += ($this->populationCalculator->getPopulation($dominion) * $populationConsumption);

        // Racial bonus
        $consumption *= (1 + $dominion->race->getPerkMultiplier('food_consumption'));

        return (float)$consumption;
    }

    /**
     * Returns the Dominion's food decay.
     *
     * @return float
     */
    public function getFoodDecay(Dominion $dominion): float
    {
        $decay = 0;

        // Values (percentages)
        $foodDecay = 1;

        $decay += ($dominion->resource_food * ($foodDecay / 100));

        return (float)$decay;
    }

    /**
     * Returns the Dominion's net food change.
     *
     * @return int
     */
    public function getFoodNetChange(Dominion $dominion): int
    {
        return (int)round($this->getFoodProduction($dominion) - $this->getFoodConsumption($dominion) - $this->getFoodDecay($dominion));
    }

    // Lumber

    /**
     * Returns the Dominion's lumber production.
     *
     * @return int
     */
    public function getLumberProduction(Dominion $dominion): int
    {
        return (int)floor($this->getLumberProductionRaw($dominion) * $this->getLumberProductionMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw lumber production.
     *
     * @return float
     */
    public function getLumberProductionRaw(Dominion $dominion): float
    {
        $lumber = 0;

        // Values
        $lumberPerLumberyard = 50;

        // Lumberyards
        $lumber += ($dominion->building_lumberyard * $lumberPerLumberyard);

        return (float)$lumber;
    }

    /**
     * Returns the Dominion's lumber production multiplier.
     *
     * @return float
     */
    public function getLumberProductionMultiplier(Dominion $dominion): float
    {
        $multiplier = 0.0;

        // Values (percentages)
        $spellGaiasBlessing = 10;
        $techProduction = 10;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('lumber_production');

        // Spell: Gaia's Blessing
        // todo

        // Tech: Production
        // todo

        return (float)(1 + $multiplier);
    }

    /**
     * Returns the Dominion's lumber decay.
     *
     * @return float
     */
    public function getLumberDecay(Dominion $dominion): float
    {
        $decay = 0;

        // Values (percentages)
        $lumberDecay = 1;

        $decay += ($dominion->resource_lumber * ($lumberDecay / 100));

        return (float)$decay;
    }

    /**
     * Returns the Dominion's net lumber change.
     *
     * @return int
     */
    public function getLumberNetChange(Dominion $dominion): int
    {
        return (int)round($this->getLumberProduction($dominion) - $this->getLumberDecay($dominion));
    }

    // Mana

    /**
     * Returns the Dominion's mana production.
     *
     * @return int
     */
    public function getManaProduction(Dominion $dominion): int
    {
        return (int)floor($this->getManaProductionRaw($dominion) * $this->getManaProductionMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw mana production.
     *
     * @return float
     */
    public function getManaProductionRaw(Dominion $dominion): float
    {
        $mana = 0;

        // Values
        $manaPerTower = 25;

        // Towers
        $mana += ($dominion->building_tower * $manaPerTower);

        return (float)$mana;
    }

    /**
     * Returns the Dominion's mana production multiplier.
     *
     * @return float
     */
    public function getManaProductionMultiplier(Dominion $dominion): float
    {
        $multiplier = 0.0;

        // Values (percentages)

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('mana_production');

        // Tech: Enchanted Lands (+15%)
        // todo

        return (float)(1 + $multiplier);
    }

    /**
     * Returns the Dominion's mana decay.
     *
     * @return float
     */
    public function getManaDecay(Dominion $dominion): float
    {
        $decay = 0;

        // Values (percentages)
        $manaDecay = 2;

        $decay += ($dominion->resource_mana * ($manaDecay / 100));

        return (float)$decay;
    }

    /**
     * Returns the Dominion's net mana change.
     *
     * @return int
     */
    public function getManaNetChange(Dominion $dominion): int
    {
        return (int)round($this->getManaProduction($dominion) - $this->getManaDecay($dominion));
    }

    // Ore

    /**
     * Returns the Dominion's ore production.
     *
     * @return int
     */
    public function getOreProduction(Dominion $dominion): int
    {
        return (int)floor($this->getOreProductionRaw($dominion) * $this->getOreProductionMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw ore production.
     *
     * @return float
     */
    public function getOreProductionRaw(Dominion $dominion): float
    {
        $ore = 0;

        // Values
        $orePerOreMine = 60;

        // Ore Mines
        $ore += ($dominion->building_ore_mine * $orePerOreMine);

        // Dwarf Unit: Miner
        // todo

        return (float)$ore;
    }

    /**
     * Returns the Dominion's ore production multiplier.
     *
     * @return float
     */
    public function getOreProductionMultiplier(Dominion $dominion): float
    {
        $multiplier = 0.0;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('ore_production');

        // Magic: Miner's Sight (Dwarf) (+20%)
        // Magic: Mining Strength (+10%)
        // todo

        // Tech: Fruits of Labor (+20%)
        // todo

        return (float)(1 + $multiplier);
    }

    // Gems

    /**
     * Returns the Dominion's gem production.
     *
     * @return int
     */
    public function getGemProduction(Dominion $dominion): int
    {
        return (int)floor($this->getGemProductionRaw($dominion) * $this->getGemProductionMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw gem production.
     *
     * @return float
     */
    public function getGemProductionRaw(Dominion $dominion): float
    {
        $gems = 0;

        // Values
        $gemsPerDiamondMine = 15;

        // Diamond Mines
        $gems += ($dominion->building_diamond_mine * $gemsPerDiamondMine);

        return (float)$gems;
    }

    /**
     * Returns the Dominion's gem production multiplier.
     *
     * @return float
     */
    public function getGemProductionMultiplier(Dominion $dominion): float
    {
        $multiplier = 0.0;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('gem_production');

        // Tech: Production (+5%)
        // Tech: Fruits of Labor (+15%)
        // todo

        return (float)(1 + $multiplier);
    }

    // Tech
    // todo

    // Boats

    /**
     * Returns the Dominion's boat production per hour.
     *
     * @return float
     */
    public function getBoatProduction(Dominion $dominion): float
    {
        return ($this->getBoatProductionRaw($dominion) * $this->getBoatProductionMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw boat production per hour.
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

        return (float)$boats;
    }

    /**
     * Returns the Dominions's boat production multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getBoatProductionMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        // Improvement: Irrigation
        $multiplier += $this->improvementCalculator->getImprovementMultiplier($dominion, 'irrigation');

        return $multiplier;
    }
}
