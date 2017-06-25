<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Contracts\Calculators\Dominion\LandCalculator;
use OpenDominion\Contracts\Calculators\Dominion\PopulationCalculator as PopulationCalculatorContract;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;

class PopulationCalculator implements PopulationCalculatorContract
{
    /** @var BuildingHelper */
    protected $buildingHelper;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var UnitHelper */
    protected $unitHelper;

    public function __construct(BuildingHelper $buildingHelper, LandCalculator $landCalculator, UnitHelper $unitHelper)
    {
        $this->buildingHelper = $buildingHelper;
        $this->landCalculator = $landCalculator;
        $this->unitHelper = $unitHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getPopulation(Dominion $dominion)
    {
        return ($dominion->peasants + $this->getPopulationMilitary($dominion));
    }

    /**
     * {@inheritdoc}
     */
    public function getPopulationMilitary(Dominion $dominion)
    {
        return (
            $dominion->military_draftees
            + $dominion->military_unit1
            + $dominion->military_unit2
            + $dominion->military_unit3
            + $dominion->military_unit4
            + $dominion->military_spies
            + $dominion->military_wizards
            + $dominion->military_archmages
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxPopulation(Dominion $dominion)
    {
        return (int)round(
            ($this->getMaxPopulationRaw($dominion) * $this->getMaxPopulationMultiplier($dominion))
            + $this->getMaxPopulationMilitaryBonus($dominion) // todo: re-check this formula
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxPopulationRaw(Dominion $dominion)
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

            $population += ($dominion->{'building_' . $buildingType} * $housing);
        }

        // Housing per barren land
        $population += ($this->landCalculator->getTotalBarrenLand($dominion) * $housingPerBarrenLand);

        return (float)$population;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxPopulationMultiplier(Dominion $dominion)
    {
        $multiplier = 0;

        // Values (percentages)
//        $techUrbanMasteryMultiplier = 7.5;
//        $techConstructionMultiplier = 2;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('max_population');

        // Improvement: Keep
        // todo

        // Tech: Urban Mastery
        // todo

        // Tech: Construction
        // todo

        // Prestige bonus
        // todo: $prestige / 10000?
        $multiplier *= (1 + (($dominion->prestige / 250) * 2.5) / 100);
        $multiplier += ((($dominion->prestige / 250) * 2.5) / 100);
        // todo: re-check this vs other prestige formulae

        /*
        todo: cleanup
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

        return (float)(1 + $multiplier); // todo: 1+$multiplier? check for refactoring
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxPopulationMilitaryBonus(Dominion $dominion)
    {
        // Values
        $troopsPerBarracks = 36;

        return (float)min(
            ($this->getPopulationMilitary($dominion) - $dominion->military_draftees), // todo: -training queue
            ($dominion->building_barracks * $troopsPerBarracks)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPopulationBirth(Dominion $dominion)
    {
        return (int)round($this->getPopulationBirthRaw($dominion) * $this->getPopulationBirthMultiplier($dominion));
    }

    /**
     * {@inheritdoc}
     */
    public function getPopulationBirthRaw(Dominion $dominion)
    {
        $birth = 0;

        // Values (percentages)
        $growthFactor = 3;

        // Growth
        $birth += (($dominion->peasants - $this->getPopulationDrafteeGrowth($dominion)) * ($growthFactor / 100));

        return (float)$birth;
    }

    /**
     * {@inheritdoc}
     */
    public function getPopulationBirthMultiplier(Dominion $dominion)
    {
        $multiplier = 1;

        // Values
        //$spellHarmony = 1.5;
        //$templeBonus = 6;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('population_growth');

        // Spell: Harmony
        // todo

        // Temples
        //$multiplier += (($this->dominion->building_temple * $templeBonus) / $this->landCalculator->getTotalLand());

        return (float)$multiplier; // todo: see 1+$multiplier todo above
    }

    /**
     * {@inheritdoc}
     */
    public function getPopulationPeasantGrowth(Dominion $dominion)
    {
        return (int)max(
            ((-0.05 * $dominion->peasants) - $this->getPopulationDrafteeGrowth($dominion)),
            min(
                ($this->getMaxPopulation($dominion) - $this->getPopulation($dominion) - $this->getPopulationDrafteeGrowth($dominion)),
                ($this->getPopulationBirth($dominion) - $this->getPopulationDrafteeGrowth($dominion))
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPopulationDrafteeGrowth(Dominion $dominion)
    {
        $draftees = 0;

        // Values (percentages)
        $growthFactor = 1;

        if ($this->getPopulationMilitaryPercentage($dominion) < $dominion->draft_rate) {
            $draftees += ($dominion->peasants * ($growthFactor / 100));
        }

        return (int)$draftees;
    }

    /**
     * {@inheritdoc}
     */
    public function getPopulationPeasantPercentage(Dominion $dominion)
    {
        return (float)(($dominion->peasants / $this->getPopulation($dominion)) * 100);
    }

    /**
     * {@inheritdoc}
     */
    public function getPopulationMilitaryPercentage(Dominion $dominion)
    {
        return (float)(($this->getPopulationMilitary($dominion) / $this->getPopulation($dominion)) * 100);
    }

//    public function getPopulationMilitaryTrainingCostPerUnit()
//    {
//        $costsPerUnit = [];
//
//        // Values
//        $spyPlatinumCost = 500;
//        $wizardPlatinumCost = 500;
//        $archmagePlatinumCost = 1000;
//
//        $units = $this->dominion->race->units;
//
//        foreach ($this->unitHelper->getUnitTypes() as $unitType) {
//            $cost = [];
//
//            switch ($unitType) {
//                case 'spies':
//                    $cost['draftees'] = 1;
//                    $cost['platinum'] = $spyPlatinumCost;
//                    break;
//
//                case 'wizards':
//                    $cost['draftees'] = 1;
//                    $cost['platinum'] = $wizardPlatinumCost;
//                    break;
//
//                case 'archmages':
//                    $cost['platinum'] = $archmagePlatinumCost;
//                    $cost['wizards'] = 1;
//                    break;
//
//                default:
//                    $unitSlot = (((int)str_replace('unit', '', $unitType)) - 1);
//
//                    $platinum = $units[$unitSlot]->cost_platinum;
//                    $ore = $units[$unitSlot]->cost_ore;
//
//                    if ($platinum > 0) {
//                        $cost['platinum'] = $platinum;
//                    }
//
//                    if ($ore > 0) {
//                        $cost['ore'] = $ore;
//                    }
//
//                    $cost['draftees'] = 1;
//
//                    break;
//            }
//
//            $costsPerUnit[$unitType] = $cost;
//        }
//
//        return $costsPerUnit;
//    }
//
//    /**
//     * Returns the Dominion's max military trainable population.
//     *
//     * @return array
//     */
//    public function getPopulationMilitaryMaxTrainable()
//    {
//        $trainable = [];
//
//        $fieldMapping = [
//            'platinum' => 'resource_platinum',
//            'ore' => 'resource_ore',
//            'draftees' => 'military_draftees',
//            'wizards' => 'military_wizards',
//        ];
//
//        $costsPerUnit = $this->getPopulationMilitaryTrainingCostPerUnit();
//
//        foreach ($costsPerUnit as $unitType => $costs) {
//            $trainableByCost = [];
//
//            foreach ($costs as $type => $value) {
//                $trainableByCost[$type] = (int)floor($this->dominion->{$fieldMapping[$type]} / $value);
//            }
//
//            $trainable[$unitType] = min($trainableByCost);
//        }
//
//        return $trainable;
//    }

    /**
     * {@inheritdoc}
     */
    public function getEmploymentJobs(Dominion $dominion)
    {
        // todo: get these from buildinghelper and unset barracks/etc
        return (20 * (
                $dominion->building_alchemy
                + $dominion->building_farm
                + $dominion->building_smithy
                + $dominion->building_masonry
                + $dominion->building_ore_mine
                + $dominion->building_gryphon_nest
                + $dominion->building_tower
                + $dominion->building_wizard_guild
                + $dominion->building_temple
                + $dominion->building_diamond_mine
                + $dominion->building_school
                + $dominion->building_lumberyard
                + $dominion->building_forest_haven
                + $dominion->building_factory
                + $dominion->building_guard_tower
                + $dominion->building_shrine
                + $dominion->building_dock
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function getPopulationEmployed(Dominion $dominion)
    {
        return (int)min($this->getEmploymentJobs($dominion), $dominion->peasants);
    }

    /**
     * {@inheritdoc}
     */
    public function getEmploymentPercentage(Dominion $dominion)
    {
        return (float)(min(1, ($this->getPopulationEmployed($dominion) / $dominion->peasants)) * 100);
    }
}
