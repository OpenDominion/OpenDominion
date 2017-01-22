<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;

class ProductionCalculator extends AbstractDominionCalculator
{
    /** @var PopulationCalculator */
    protected $populationCalculator;

    /**
     * {@inheritDoc}
     */
    public function initDependencies()
    {
        $this->populationCalculator = app()->make(PopulationCalculator::class);
    }

    /**
     * {@inheritDoc}
     */
    public function init(Dominion $dominion)
    {
        parent::init($dominion);

        $this->populationCalculator->setDominion($dominion);

        return $this;
    }

    // Platinum

    /**
     * Returns the Dominion's platinum production.
     *
     * @return int
     */
    public function getPlatinumProduction()
    {
        return (int)floor($this->getPlatinumProductionRaw() * $this->getPlatinumProductionMultiplier());
    }

    /**
     * Returns the Dominion's raw platinum production.
     *
     * @return float
     */
    public function getPlatinumProductionRaw()
    {
        $platinum = 0;

        // Values
        $peasantTax = 2.7;
        $spellAlchemistFlameBonus = 15;
        $platinumPerAlchemy = 45;

        // Peasant Tax
        $platinum += (($this->dominion->peasants * $peasantTax) * ($this->populationCalculator->getEmploymentPercentage() / 100));

        // Spell: Alchemist Flame
        // todo

        // Alchemies
        $platinum += ($this->dominion->building_alchemy * $platinumPerAlchemy);

        return (float)$platinum;
    }

    /**
     * Returns the Dominion's platinum production multiplier.
     *
     * @return float
     */
    public function getPlatinumProductionMultiplier()
    {
        $multiplier = 1.0;

        // Values (percentages)
        $spellMidasTouch = 10;
        $guardTax = -2;
        $techProduction = 5;
        $techTreasureHunt = 12.5;

        // Racial bonus
        //$multiplier += $this->dominion->race->getPerkMultiplier('platinum_production');
        // todo

        // Spell: Midas Touch
        // todo

        // Improvement: Science
        // todo

        // Guard Tax
        // todo

        // Tech: Production or Treasure Hunt
        // todo

        return (float)min(1.5, $multiplier);
    }

    // Food

    /**
     * Returns the Dominion's food production.
     *
     * @return int
     */
    public function getFoodProduction()
    {
        return (int)floor($this->getFoodProductionRaw() * $this->getFoodProductionMultiplier());
    }

    /**
     * Returns the Dominion's raw food production.
     *
     * @return float
     */
    public function getFoodProductionRaw()
    {
        $food = 0;

        // Values
        $foodPerFarm = 80;
        $foodPerDock = 35;

        // Farms
        $food += ($this->dominion->building_farm * $foodPerFarm);

        // Docks
        $food += ($this->dominion->building_dock * $foodPerDock);

        return (float)$food;
    }

    /**
     * Returns the Dominion's food production multiplier.
     *
     * @return float
     */
    public function getFoodProductionMultiplier()
    {
        $multiplier = 0;

        // Values (percentages)
        $spellGaiasBlessing = 20;
        $spellGaiasWatch = 10;
        $techProduction = 10;

        // Racial bonus
        $multiplier += $this->dominion->race->getPerkMultiplier('food_production');

        // Spell: Gaia's Blessing
        // todo

        // Spell: Gaia's Watch
        // todo

        // Improvement: Irrigation
        // todo

        // Tech: Production
        // todo

        // Prestige bonus multiplier
        $multiplier *= (1 + (($this->dominion->prestige / 250) * 2.5) / 100);
        $multiplier += ((($this->dominion->prestige / 250) * 2.5) / 100);

        return (float)(1 + $multiplier);
    }

    /**
     * Returns the Dominion's food consumption.
     *
     * @return float
     */
    public function getFoodConsumption()
    {
        $consumption = 0;

        // Values
        $populationConsumption = 0.25;

        // Population consumption
        $consumption += ($this->populationCalculator->getPopulation() * $populationConsumption);

        // Racial bonus
        $consumption *= (1 + $this->dominion->race->getPerkMultiplier('food_consumption'));

        return (float)$consumption;
    }

    /**
     * Returns the Dominion's food decay.
     *
     * @return float
     */
    public function getFoodDecay()
    {
        $decay = 0;

        // Values (percentages)
        $foodDecay = 1;

        $decay += ($this->dominion->resource_food * ($foodDecay / 100));

        return (float)$decay;
    }

    /**
     * Returns the Dominion's net food change.
     *
     * @return int
     */
    public function getFoodNetChange()
    {
        return (int)round($this->getFoodProduction() - $this->getFoodConsumption() - $this->getFoodDecay());
    }

    // Lumber

    /**
     * Returns the Dominion's lumber production.
     *
     * @return int
     */
    public function getLumberProduction()
    {
        return (int)floor($this->getLumberProductionRaw() * $this->getLumberProductionMultiplier());
    }

    /**
     * Returns the Dominion's raw lumber production.
     *
     * @return float
     */
    public function getLumberProductionRaw()
    {
        $lumber = 0;

        // Values
        $lumberPerLumberyard = 50;

        // Lumberyards
        $lumber += ($this->dominion->building_lumberyard * $lumberPerLumberyard);

        return (float)$lumber;
    }

    /**
     * Returns the Dominion's lumber production multiplier.
     *
     * @return float
     */
    public function getLumberProductionMultiplier()
    {
        $multiplier = 1.0;

        // Values (percentages)
        $spellGaiasBlessing = 10;
        $techProduction = 10;

        // Racial bonus
        $multiplier += $this->dominion->race->getPerkMultiplier('lumber_production');

        // Spell: Gaia's Blessing
        // todo

        // Tech: Production
        // todo

        return $multiplier;
    }

    /**
     * Returns the Dominion's lumber decay.
     *
     * @return float
     */
    public function getLumberDecay()
    {
        $decay = 0;

        // Values (percentages)
        $lumberDecay = 1;

        $decay += ($this->dominion->resource_lumber * ($lumberDecay / 100));

        return (float)$decay;
    }

    /**
     * Returns the Dominion's net lumber change.
     *
     * @return int
     */
    public function getLumberNetChange()
    {
        return (int)round($this->getLumberProduction() - $this->getLumberDecay());
    }

    // Mana

    /**
     * Returns the Dominion's mana production.
     *
     * @return int
     */
    public function getManaProduction()
    {
        return (int)floor($this->getManaProductionRaw() * $this->getManaProductionMultiplier());
    }

    /**
     * Returns the Dominion's raw mana production.
     *
     * @return float
     */
    public function getManaProductionRaw()
    {
        $mana = 0;

        // Values
        $manaPerTower = 25;

        // Towers
        $mana += ($this->dominion->building_tower * $manaPerTower);

        return (float)$mana;
    }

    /**
     * Returns the Dominion's mana production multiplier.
     *
     * @return float
     */
    public function getManaProductionMultiplier()
    {
        $multiplier = 0.0;

        // Values (percentages)

        // Racial Bonus
        $multiplier += $this->dominion->race->getPerkMultiplier('mana_production');

        // Tech: Enchanted Lands (+15%)
        // todo

        return (float)(1 + $multiplier);
    }

    // Ore
    // todo

    // Gems
    // todo

    // Tech
    // todo

    // Boats

    /**
     * Returns the Dominion's boat production.
     *
     * @return int
     */
    public function getBoatProduction()
    {
        $boats = 0;

        // Values
        $docksPerBoat = 20;

        // todo: store boats as float? i.e +1 boat every 20 hours with 1 dock
        $boats += floor($this->dominion->building_dock / $docksPerBoat);

        return (int)$boats;
    }
}
