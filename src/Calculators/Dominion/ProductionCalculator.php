<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Contracts\Calculators\Dominion\ProductionCalculator as ProductionCalculatorContract;
use OpenDominion\Models\Dominion;

class ProductionCalculator implements ProductionCalculatorContract
{
    /** @var PopulationCalculator */
    protected $populationCalculator;

    /**
     * ProductionCalculator constructor.
     *
     * @param PopulationCalculator $populationCalculator
     */
    public function __construct(PopulationCalculator $populationCalculator)
    {
        $this->populationCalculator = $populationCalculator;
    }

    // Platinum

    /**
     * {@inheritdoc}
     */
    public function getPlatinumProduction(Dominion $dominion)
    {
        return (int)floor($this->getPlatinumProductionRaw($dominion) * $this->getPlatinumProductionMultiplier($dominion));
    }

    /**
     * {@inheritdoc}
     */
    public function getPlatinumProductionRaw(Dominion $dominion)
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
     * {@inheritdoc}
     */
    public function getPlatinumProductionMultiplier(Dominion $dominion)
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
        // todo

        // Guard Tax
        // todo

        // Tech: Production or Treasure Hunt
        // todo

        return (float)min(1.5, (1 + $multiplier));
    }

    // Food

    /**
     * {@inheritdoc}
     */
    public function getFoodProduction(Dominion $dominion)
    {
        return (int)floor($this->getFoodProductionRaw($dominion) * $this->getFoodProductionMultiplier($dominion));
    }

    /**
     * {@inheritdoc}
     */
    public function getFoodProductionRaw(Dominion $dominion)
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
     * {@inheritdoc}
     */
    public function getFoodProductionMultiplier(Dominion $dominion)
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
        // todo

        // Tech: Production
        // todo

        // Prestige bonus multiplier
        $multiplier *= (1 + (($dominion->prestige / 250) * 2.5) / 100);
        $multiplier += ((($dominion->prestige / 250) * 2.5) / 100);

        return (float)(1 + $multiplier);
    }

    /**
     * {@inheritdoc}
     */
    public function getFoodConsumption(Dominion $dominion)
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
     * {@inheritdoc}
     */
    public function getFoodDecay(Dominion $dominion)
    {
        $decay = 0;

        // Values (percentages)
        $foodDecay = 1;

        $decay += ($dominion->resource_food * ($foodDecay / 100));

        return (float)$decay;
    }

    /**
     * {@inheritdoc}
     */
    public function getFoodNetChange(Dominion $dominion)
    {
        return (int)round($this->getFoodProduction($dominion) - $this->getFoodConsumption($dominion) - $this->getFoodDecay($dominion));
    }

    // Lumber

    /**
     * {@inheritdoc}
     */
    public function getLumberProduction(Dominion $dominion)
    {
        return (int)floor($this->getLumberProductionRaw($dominion) * $this->getLumberProductionMultiplier($dominion));
    }

    /**
     * {@inheritdoc}
     */
    public function getLumberProductionRaw(Dominion $dominion)
    {
        $lumber = 0;

        // Values
        $lumberPerLumberyard = 50;

        // Lumberyards
        $lumber += ($dominion->building_lumberyard * $lumberPerLumberyard);

        return (float)$lumber;
    }

    /**
     * {@inheritdoc}
     */
    public function getLumberProductionMultiplier(Dominion $dominion)
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
     * {@inheritdoc}
     */
    public function getLumberDecay(Dominion $dominion)
    {
        $decay = 0;

        // Values (percentages)
        $lumberDecay = 1;

        $decay += ($dominion->resource_lumber * ($lumberDecay / 100));

        return (float)$decay;
    }

    /**
     * {@inheritdoc}
     */
    public function getLumberNetChange(Dominion $dominion)
    {
        return (int)round($this->getLumberProduction($dominion) - $this->getLumberDecay($dominion));
    }

    // Mana

    /**
     * {@inheritdoc}
     */
    public function getManaProduction(Dominion $dominion)
    {
        return (int)floor($this->getManaProductionRaw($dominion) * $this->getManaProductionMultiplier($dominion));
    }

    /**
     * {@inheritdoc}
     */
    public function getManaProductionRaw(Dominion $dominion)
    {
        $mana = 0;

        // Values
        $manaPerTower = 25;

        // Towers
        $mana += ($dominion->building_tower * $manaPerTower);

        return (float)$mana;
    }

    /**
     * {@inheritdoc}
     */
    public function getManaProductionMultiplier(Dominion $dominion)
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
     * {@inheritdoc}
     */
    public function getManaDecay(Dominion $dominion)
    {
        $decay = 0;

        // Values (percentages)
        $manaDecay = 2;

        $decay += ($dominion->resource_mana * ($manaDecay / 100));

        return (float)$decay;
    }

    /**
     * {@inheritdoc}
     */
    public function getManaNetChange(Dominion $dominion)
    {
        return (int)round($this->getManaProduction($dominion) - $this->getManaDecay($dominion));
    }

    // Ore

    /**
     * {@inheritdoc}
     */
    public function getOreProduction(Dominion $dominion)
    {
        return (int)floor($this->getOreProductionRaw($dominion) * $this->getOreProductionMultiplier($dominion));
    }

    /**
     * {@inheritdoc}
     */
    public function getOreProductionRaw(Dominion $dominion)
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
     * {@inheritdoc}
     */
    public function getOreProductionMultiplier(Dominion $dominion)
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
     * {@inheritdoc}
     */
    public function getGemProduction(Dominion $dominion)
    {
        return (int)floor($this->getGemProductionRaw($dominion) * $this->getGemProductionMultiplier($dominion));
    }

    /**
     * {@inheritdoc}
     */
    public function getGemProductionRaw(Dominion $dominion)
    {
        $gems = 0;

        // Values
        $gemsPerDiamondMine = 15;

        // Diamond Mines
        $gems += ($dominion->building_diamond_mine * $gemsPerDiamondMine);

        return (float)$gems;
    }

    /**
     * {@inheritdoc}
     */
    public function getGemProductionMultiplier(Dominion $dominion)
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
     * {@inheritdoc}
     */
    public function getBoatProduction(Dominion $dominion)
    {
        $boats = 0;

        // Values
        $docksPerBoat = 20;

        // todo: store boats as float? i.e +1 boat every 20 hours with 1 dock
        $boats += floor($dominion->building_dock / $docksPerBoat);

        return (int)$boats;
    }
}
