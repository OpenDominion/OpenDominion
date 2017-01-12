<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;

class ProductionCalculator extends AbstractDominionCalculator
{
    /** @var PopulationCalculator */
    protected $populationCalculator;

    public function __construct(Dominion $dominion)
    {
        parent::__construct($dominion);

        $this->populationCalculator = app()->make(PopulationCalculator::class, [$dominion]);
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

        // Farms
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
        //$multiplier += $this->dominion->race->getPerkMultiplier('food_production');
        // todo

        // Spell: Gaia's Blessing
        // todo

        // Spell: Gaia's Watch
        // todo

        // Improvement: Irrigation
        // todo

        // Tech: Production
        // todo

        // Prestige bonus
        $multiplier *= (1 + (($this->dominion->prestige / 250) * 2.5) / 100);
        $multiplier += ((($this->dominion->prestige / 250) * 2.5) / 100);
        $multiplier += 1;

//        $multiplier =
//            (
//                0.05
//                + 0 // magic
//                + 0 // improvements
//            )
//            * (1 + (($this->dominion->prestige / 250) * 2.5) / 100)
//            + ((($this->dominion->prestige / 250) * 2.5) / 100);

        /*

        = ($Overview.$I$18 // +5% racial food bonus
            + IF(
                $Magic.AF3>0;
                $Constants.$F$80;
                IF(
                    $Magic.X3>0;
                    $Constants.$F$72
                )
             )
            + $Imps.AD3
            + $Constants.$M$30 * $Techs.W3
        )
        * ( 1 + ROUNDDOWN(O3 /250 * $Constants.$B$90; 2) / 100)
        + ROUNDDOWN( O3 / 250 * $Constants.$B$90; 2) / 100

        */

        return (float)$multiplier;
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
        //$consumption *= (1 + $this->dominion->race->getPerkMultiplier('food_consumption'));
        // todo

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
     * @return float
     */
    public function getFoodNetChange()
    {
        return (float)($this->getFoodProduction() - $this->getFoodConsumption() - $this->getFoodDecay());
    }

    // Lumber

    public function getLumberProduction()
    {
        return 0;
    }

    public function getLumberProductionRaw()
    {
        return 0;
    }

    public function getLumberProductionMultiplier()
    {
        return 0;
    }

    public function getLumberDecay()
    {
        return 0;
    }

    // todo: getLumberNetChange?

    // Mana

    // Ore

    // Gems

    // Boats
}
