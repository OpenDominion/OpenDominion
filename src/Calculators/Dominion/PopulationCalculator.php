<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Models\Dominion;

class PopulationCalculator extends AbstractDominionCalculator
{
    /** @var BuildingHelper */
    protected $buildingHelper;

    /** @var LandCalculator */
    protected $landCalculator;

    /**
     * {@inheritDoc}
     */
    public function init(Dominion $dominion)
    {
        parent::init($dominion);

        $this->buildingHelper = app()->make(BuildingHelper::class);
        $this->landCalculator = app()->make(LandCalculator::class)->setDominion($dominion);
    }

    /**
     * Returns the Dominion's total population, both peasants and military.
     *
     * @return int
     */
    public function getPopulation()
    {
        return (int)($this->dominion->peasants + $this->getPopulationMilitary());
    }

    /**
     * Returns the Dominion's military population.
     *
     * The military consists of draftees, combat units, spies, wizards and archmages.
     *
     * @return int
     */
    public function getPopulationMilitary()
    {
        return (int)(
            $this->dominion->military_draftees
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

        // Peasants
        $population += ($this->getMaxPopulationRaw() * $this->getMaxPopulationMultiplier());

        // Military
        $population += min(
            ($this->getPopulationMilitary() - $this->dominion->military_draftees),
            ($this->dominion->building_barracks * $troopsPerBarracks)
        );

        return (int)round($population);
    }

    /**
     * Returns the Dominion's raw max population.
     *
     * Maximum population is determined by housing in homes, other buildings (sans barracks) and barren land.
     *
     * @return float
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

        foreach ($this->buildingHelper->getBuildingTypes() as $buildingType) {
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

        return (float)$population;
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
        $multiplier = 0;

        // Values (percentages)
//        $techUrbanMasteryMultiplier = 7.5;
//        $techConstructionMultiplier = 2;

        // Racial bonus
        // todo

        // Improvement: Keep
        // todo

        // Tech: Urban Mastery
        // todo

        // Tech: Construction
        // todo

        // Prestige bonus
        // todo: $prestige / 10000?
        $multiplier *= (1 + (($this->dominion->prestige / 250) * 2.5) / 100);
        $multiplier += ((($this->dominion->prestige / 250) * 2.5) / 100);
        $multiplier += 1;

        /*
        = ($Overview.$I$15
            + $Imps.Z3
            + MAX(
                $Constants.$M$38 * $Techs.AE3;
                $Constants.$M$50 * $Techs.AQ3
            )
        )
        * (1 + ROUNDDOWN($Production.O3 / 250 * $Constants.$B$90; 2) / 100)
        + ROUNDDOWN($Production.O3 / 250 * $Constants.$B$90; 2) / 100
        */

        return (float)$multiplier;
    }

    /**
     * Returns the Dominion's population birth.
     *
     * @return int
     */
    public function getPopulationBirth()
    {
        return (int)round($this->getPopulationBirthRaw() * $this->getPopulationBirthMultiplier());
    }

    /**
     * Returns the Dominions raw population birth.
     *
     * @return float
     */
    public function getPopulationBirthRaw()
    {
        $birth = 0;

        // Values (percentages)
        $growthFactor = 3;

        // Growth
        $birth += (($this->dominion->peasants - $this->getPopulationDrafteeGrowth()) * ($growthFactor / 100));

        return (float)$birth;
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

        return (float)$multiplier;
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
                ($this->getMaxPopulation() - $this->getPopulation() - $this->getPopulationDrafteeGrowth()),
                ($this->getPopulationBirth() - $this->getPopulationDrafteeGrowth())
            )
        );
    }

    /**
     * Returns the Dominion's population draftee growth.
     *
     * Draftee growth is influenced by draft rate.
     *
     * @return int
     */
    public function getPopulationDrafteeGrowth()
    {
        $draftees = 0;

        // Values (percentages)
        $growthFactor = 1;

        if ($this->getPopulationMilitaryPercentage() < $this->dominion->draft_rate) {
            $draftees += ($this->dominion->peasants * ($growthFactor / 100));
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
        return (float)(($this->dominion->peasants / $this->getPopulation()) * 100);
    }

    /**
     * Returns the Dominion's population military percentage.
     *
     * @return float
     */
    public function getPopulationMilitaryPercentage()
    {
        return (float)(($this->getPopulationMilitary() / $this->getPopulation()) * 100);
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

        $units = $this->dominion->race->units;

        for ($i = 0; $i < 4; $i++) {
            $slot = ($i + 1);

            $trainable['unit' . $slot] = min(
                $this->dominion->military_draftees,
                floor($this->dominion->resource_platinum / $units->get($i)->cost_platinum),
                floor($this->dominion->resource_ore / $units->get($i)->cost_ore)
            );
        }

        $trainable['spies'] = min($this->dominion->military_draftees, floor($this->dominion->resource_platinum / $spyPlatinumCost));
        $trainable['wizards'] = min($this->dominion->military_draftees, floor($this->dominion->resource_platinum / $wizardPlatinumCost));
        $trainable['archmages'] = min($this->dominion->military_wizards, floor($this->dominion->resource_platinum / $archmagePlatinumCost));

        return $trainable;
    }

    /**
     * Returns the Dominion's employment jobs.
     *
     * Each building (sans home and barracks) employs 20 peasants.
     *
     * @return int
     */
    public function getEmploymentJobs()
    {
        return (20 * (
                $this->dominion->building_alchemy
                + $this->dominion->building_farm
//            + $this->dominion->building_smithy
//            + $this->dominion->building_masonry
//            + $this->dominion->building_ore_mine
//            + $this->dominion->building_gryphon_nest
//            + $this->dominion->building_tower
//            + $this->dominion->building_wizard_guild
//            + $this->dominion->building_temple
//            + $this->dominion->building_diamond_mine
//            + $this->dominion->building_school
                + $this->dominion->building_lumberyard
//            + $this->dominion->building_forest_haven
//            + $this->dominion->building_factory
//            + $this->dominion->building_guard_tower
//            + $this->dominion->building_shrine
//            + $this->dominion->building_dock
            ));
    }

    /**
     * Returns the Dominion's employed population.
     *
     * The employed population consists of the Dominion's peasant count, up to the number of max available jobs.
     *
     * @return int
     */
    public function getPopulationEmployed()
    {
        return (int)min($this->getEmploymentJobs(), $this->dominion->peasants);
    }

    /**
     * Returns the Dominion's employment percentage.
     *
     * If employment is at or above 100%, then one should strive to build more homes to get more peasants to the working
     * force. If employment is below 100%, then one should construct more buildings to employ idle peasants.
     *
     * @return float
     */
    public function getEmploymentPercentage()
    {
        return (float)(min(1, ($this->getPopulationEmployed() / $this->dominion->peasants)) * 100);
    }
}
