<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;

class ProductionCalculator extends AbstractDominionCalculator
{
    /** @var EmploymentCalculator */
    protected $employmentCalculator;

    public function __construct(Dominion $dominion)
    {
        parent::__construct($dominion);

        $this->employmentCalculator = app()->make(EmploymentCalculator::class, [$dominion]);
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
        $platinum += (($this->dominion->peasants * $peasantTax) * ($this->employmentCalculator->getEmploymentPercentage() / 100));

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

        // Values
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

        // Tech: Productio or Treasure Hunt
        // todo

        return (float)min(1.5, $multiplier);
    }

    // Food

    public function getFoodProduction()
    {
        return 0;
    }

    public function getFoodProductionRaw()
    {
        return 0;
    }

    public function getFoodProductionMultiplier()
    {
        return 0;
    }

    public function getFoodConsumption()
    {
        return 0;
    }

    public function getFoodDecay()
    {
        return 0;
    }

    // todo: needed?
    public function getFoodNetChange()
    {
        return 0;
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
