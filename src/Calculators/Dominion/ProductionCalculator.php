<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Traits\DominionAwareTrait;

class ProductionCalculator
{
    use DominionAwareTrait;

    // Platinum

    public function getPlatinumProduction()
    {
        return ($this->getPlatinumProductionRaw() * $this->getPlatinumProductionMultiplier());
    }

    public function getPlatinumProductionRaw()
    {
        $platinum = 0;

        // Values
        $peasantTax = 2.7;
        $spellAlchemistFlameBonus = 15;
        $platinumPerAlchemy = 45;

        // Peasant Tax
//        $platinum += (($this->dominion->peasants * $peasantTax) * (/*employment percentage*/ 100 / 100));
        // todo

        // Spell: Alchemist Flame
        // todo

        // Alchemies
        $platinum += ($this->dominion->building_alchemy * $platinumPerAlchemy);

        return $platinum;
    }

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

        return min(1.5, $multiplier);
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

    // Mana

    // Ore

    // Gems

    // Boats
}
