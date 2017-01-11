<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;
use OpenDominion\Traits\DominionAwareTrait;

class PopulationCalculator extends AbstractDominionCalculator
{
    use DominionAwareTrait;

    /** @var LandCalculator */
    protected $landCalculator;

    public function __construct(Dominion $dominion)
    {
        parent::__construct($dominion);

        $this->landCalculator = app()->make(LandCalculator::class, [$dominion]);
    }

    /**
     * Returns the Dominion's population, military and non-military.
     *
     * @return int
     */
    public function getPopulation()
    {
        return ($this->dominion->peasants + $this->getPopulationMilitary());
    }

    /**
     * Returns the Dominion's military population.
     *
     * @return int
     */
    public function getPopulationMilitary()
    {
        return (
            $this->dominion->draftees
            + $this->dominion->military_unit1
            + $this->dominion->military_unit2
            + $this->dominion->military_unit3
            + $this->dominion->military_unit4
            + $this->dominion->military_spies
            + $this->dominion->military_wizards
            + $this->dominion->military_archmages
        );
    }

    /**
     * Returns the Dominion's max population.
     *
     * @return int
     */
    public function getMaxPopulation()
    {
        $population = 0;

        // Values
        $troopsPerBarracks = 36;

        // Raw pop * multiplier
        $population += ($this->getMaxPopulationRaw() * $this->getMaxPopulationMultiplier());

        // Military
        $population += min(
            ($this->getPopulationMilitary() - $this->dominion->draftees),
            ($this->dominion->building_barracks * $troopsPerBarracks)
        );

        return round($population);
    }

    /**
     * Returns the Dominion's raw max population.
     *
     * Raw max population is calculated by buildings (except barracks) and barren land.
     *
     * @return int
     */
    public function getMaxPopulationRaw()
    {
        $population = 0;

        // Values
        $housingPerHome = 30;
        $housingPerNonHome = 15; // except barracks
        $housingPerBarracks = 0;
        $housingPerBarrenLand = 5;

        // todo: race bonus for barren land

        $buildingTypes = []; // todo: BuildingHelper::getBuildingTypeS()

        foreach (array_keys($buildingTypes) as $buildingType) {
            $housing = 0;

            switch ($buildingType) {
                case 'home':
                    $housing = $housingPerHome;
                    break;

                case 'barracks':
                    $housing = $housingPerBarracks;
                    break;

                default:
                    $housing = $housingPerNonHome;
                    break;
            }

            $population += ($this->dominion->{'building_' . $buildingType} * $housing);
        }

        // Housing per barren land
        $population += ($this->landCalculator->getTotalBarrenLand() * $housingPerBarrenLand);

        return $population;
    }

    /**
     * Returns the Dominion's max population multiplier.
     *
     * Max population multiplier is affected by:
     * - Racial bonuses (todo)
     * - Improvement: Keep (todo)
     * - Tech: Urban Mastery and Construction (todo)
     * - Prestige bonus
     *
     * @return float
     */
    public function getMaxPopulationMultiplier()
    {
        $multiplier = 1.0;

        // Values
//        $techUrbanMasteryMultiplier = 1.075;
//        $techConstructionMultiplier = 1.02;

        // Racial bonus
        // todo

        // Improvement: Keep
        // todo

        // Tech: Urban Mastery
        // todo

        // Tech: Construction
        // todo

        // Prestige bonus
        $multiplier *= (1 + ($this->dominion->prestige / 10000));

        return $multiplier;
    }

    /**
     * Returns the Dominion's population birth.
     *
     * @return int
     */
    public function getPopulationBirth()
    {
        return round($this->getPopulationBirthRaw() * $this->getPopulationBirthMultiplier());
    }

    /**
     * Returns the Dominions raw population birth.
     *
     * @return float
     */
    public function getPopulationBirthRaw()
    {
        $birth = 0;

        // Values
        $growthFactor = 1.03;

        // Growth
        $birth += (($this->dominion->peasants - $this->getPopulationDrafteeGrowth()) * $growthFactor);

        return $birth;
    }

    /**
     * Returns the Dominion's population birth multiplier.
     *
     * @return float
     */
    public function getPopulationBirthMultiplier()
    {
        $multiplier = 1;

        // Values
        //$spellHarmony = 1.5;
        //$templeBonus = 6;

        // Racial bonus
        // todo

        // Spell: Harmony
        // todo

        // Temples
        //$multiplier += (($this->dominion->building_temple * $templeBonus) / $this->landCalculator->getTotalLand());

        return $multiplier;
    }

    /**
     * Returns the Dominion's population peasant growth.
     *
     * @return int
     */
    public function getPopulationPeasantGrowth()
    {
        return (int)max(
            ((-0.05 * $this->dominion->peasants) - $this->getPopulationDrafteeGrowth()),
            min(
                ($this->getMaxPopulation() - $this->dominion->peasants - $this->getPopulationMilitary() - $this->getPopulationDrafteeGrowth()),
                ($this->getPopulationBirth() - $this->getPopulationDrafteeGrowth())
            )
        );
    }

    /**
     * Returns the Dominion's population draftee growth.
     *
     * @return int
     */
    public function getPopulationDrafteeGrowth()
    {
        $draftees = 0;

        // Values (percentages)
        $growth_factor = 1;

        if ($this->getPopulationMilitaryPercentage() < $this->dominion->draft_rate) {
            $draftees += ($this->dominion->peasants * ($growth_factor / 100));
        }

        return (int)$draftees;
    }

    /**
     * Returns the Dominion's population peasant percentage.
     *
     * @return float
     */
    public function getPopulationPeasantPercentage()
    {
        return (($this->dominion->peasants / $this->getPopulation()) * 100);
    }

    /**
     * Returns the Dominion's population military percentage.
     *
     * @return float
     */
    public function getPopulationMilitaryPercentage()
    {
        return (($this->getPopulationMilitary() / $this->getPopulation()) * 100);
    }

    /**
     * Returns the Dominion's max military trainable population.
     *
     * @return array
     */
    public function getPopulationMilitaryMaxTrainable()
    {
        $trainable = [];

        // Values
        $spyPlatinumCost = 500;
        $wizardPlatinumCost = 500;
        $archmagePlatinumCost = 1000;

        $units = $this->dominion->race->units();

        for ($i = 0; $i < 4; $i++) {
            $slot = ($i + 1);

            $trainable['unit' . $slot] = min(
                $this->dominion->military_draftees,
                floor($this->dominion->resource_platinum / $units[$i]->cost_platinum),
                floor($this->dominion->resource_ore / $units[$i]->cost_ore)
            );
        }

        $trainable['spies'] = min($this->dominion->military_draftees, floor($this->dominion->resource_platinum / $spyPlatinumCost));
        $trainable['wizards'] = min($this->dominion->military_draftees, floor($this->dominion->resource_platinum / $wizardPlatinumCost));
        $trainable['archmages'] = min($this->dominion->military_wizards, floor($this->dominion->resource_platinum / $archmagePlatinumCost));

        return $trainable;
    }
}
