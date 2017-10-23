<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\Queue\TrainingQueueService;

class PopulationCalculator
{
    /** @var BuildingHelper */
    protected $buildingHelper;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var TrainingQueueService */
    protected $trainingQueueService;

    /** @var UnitHelper */
    protected $unitHelper;

    /**
     * PopulationCalculator constructor.
     *
     * @param BuildingHelper $buildingHelper
     * @param ImprovementCalculator $improvementCalculator
     * @param LandCalculator $landCalculator
     * @param SpellCalculator $spellCalculator
     * @param TrainingQueueService $trainingQueueService
     * @param UnitHelper $unitHelper
     */
    public function __construct(
        BuildingHelper $buildingHelper,
        ImprovementCalculator $improvementCalculator,
        LandCalculator $landCalculator,
        SpellCalculator $spellCalculator,
        TrainingQueueService $trainingQueueService,
        UnitHelper $unitHelper
    ) {
        $this->buildingHelper = $buildingHelper;
        $this->improvementCalculator = $improvementCalculator;
        $this->landCalculator = $landCalculator;
        $this->spellCalculator = $spellCalculator;
        $this->trainingQueueService = $trainingQueueService;
        $this->unitHelper = $unitHelper;
    }

    /**
     * Returns the Dominion's total population, both peasants and military.
     *
     * @return int
     */
    public function getPopulation(Dominion $dominion): int
    {
        return ($dominion->peasants + $this->getPopulationMilitary($dominion));
    }

    /**
     * Returns the Dominion's military population.
     *
     * The military consists of draftees, combat units, spies, wizards and archmages.
     *
     * @return int
     */
    public function getPopulationMilitary(Dominion $dominion): int
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
     * Returns the Dominion's max population.
     *
     * @return int
     */
    public function getMaxPopulation(Dominion $dominion): int
    {
        return (int)round(
            ($this->getMaxPopulationRaw($dominion) * $this->getMaxPopulationMultiplier($dominion))
            + $this->getMaxPopulationMilitaryBonus($dominion)
        );
    }

    /**
     * Returns the Dominion's raw max population.
     *
     * Maximum population is determined by housing in homes, other buildings (sans barracks) and barren land.
     *
     * @return float
     */
    public function getMaxPopulationRaw(Dominion $dominion): float
    {
        $population = 0;

        // Values
        $housingPerHome = 30;
        $housingPerNonHome = 15; // except barracks
        $housingPerBarracks = 0;
        $housingPerBarrenLand = 5;

        // todo: race bonus for barren land

        // Constructed buildings
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

        // Constructing buildings
        // todo

        // Barren land
        $population += ($this->landCalculator->getTotalBarrenLand($dominion) * $housingPerBarrenLand);

        return (float)$population;
    }

    /**
     * Returns the Dominion's max population multiplier.
     *
     * Max population multiplier is affected by:
     * - Racial Bonus
     * - Improvement: Keep
     * - Tech: Urban Mastery and Construction (todo)
     * - Prestige bonus (multiplicative)
     *
     * @return float
     */
    public function getMaxPopulationMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Values (percentages)
        $techUrbanMasteryMultiplier = 7.5;
        $techConstructionMultiplier = 2;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('max_population');

        // Improvement: Keep
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'keep');

        // Tech: Urban Mastery or Construction
        // todo

        // Prestige Bonus
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
     * Returns the Dominion's max population military bonus.
     *
     * @return float
     */
    public function getMaxPopulationMilitaryBonus(Dominion $dominion): float
    {
        // Values
        $troopsPerBarracks = 36;

        return (float)min(
            ($this->getPopulationMilitary($dominion) - $dominion->military_draftees - $this->trainingQueueService->getQueueTotal($dominion)),
            ($dominion->building_barracks * $troopsPerBarracks)
        );
    }

    /**
     * Returns the Dominion's population birth.
     *
     * @return int
     */
    public function getPopulationBirth(Dominion $dominion): int
    {
        return (int)round($this->getPopulationBirthRaw($dominion) * $this->getPopulationBirthMultiplier($dominion));
    }

    /**
     * Returns the Dominions raw population birth.
     *
     * @return float
     */
    public function getPopulationBirthRaw(Dominion $dominion): float
    {
        $birth = 0;

        // Values (percentages)
        $growthFactor = 3;

        // Growth
        $birth += (($dominion->peasants - $this->getPopulationDrafteeGrowth($dominion)) * ($growthFactor / 100));

        return (float)$birth;
    }

    /**
     * Returns the Dominion's population birth multiplier.
     *
     * @return float
     */
    public function getPopulationBirthMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        // Values (percentages)
        $spellHarmony = 50;
        $templeBonus = 6;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('population_growth');

        // Spell: Harmony
        $multiplier += $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, 'harmony', $spellHarmony);

        // Temples
        $multiplier += (($dominion->building_temple / $this->landCalculator->getTotalLand($dominion)) * $templeBonus);

        return (float)$multiplier; // todo: see 1+$multiplier todo above
    }

    /**
     * Returns the Dominion's population peasant growth.
     *
     * @return int
     */
    public function getPopulationPeasantGrowth(Dominion $dominion): int
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
     * Returns the Dominion's population draftee growth.
     *
     * Draftee growth is influenced by draft rate.
     *
     * @return int
     */
    public function getPopulationDrafteeGrowth(Dominion $dominion): int
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
     * Returns the Dominion's population peasant percentage.
     *
     * @return float
     */
    public function getPopulationPeasantPercentage(Dominion $dominion): float
    {
        if (($dominionPopulation = $this->getPopulation($dominion)) === 0) {
            return (float)0;
        }

        return (float)(($dominion->peasants / $dominionPopulation) * 100);
    }

    /**
     * Returns the Dominion's population military percentage.
     *
     * @return float
     */
    public function getPopulationMilitaryPercentage(Dominion $dominion): float
    {
        if (($dominionPopulation = $this->getPopulation($dominion)) === 0) {
            return (float)0;
        }

        return (float)(($this->getPopulationMilitary($dominion) / $dominionPopulation) * 100);
    }

    /**
     * Returns the Dominion's employment jobs.
     *
     * Each building (sans home and barracks) employs 20 peasants.
     *
     * @return int
     */
    public function getEmploymentJobs(Dominion $dominion): int
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
     * Returns the Dominion's employed population.
     *
     * The employed population consists of the Dominion's peasant count, up to the number of max available jobs.
     *
     * @return int
     */
    public function getPopulationEmployed(Dominion $dominion): int
    {
        return (int)min($this->getEmploymentJobs($dominion), $dominion->peasants);
    }

    /**
     * Returns the Dominion's employment percentage.
     *
     * If employment is at or above 100%, then one should strive to build more homes to get more peasants to the working
     * force. If employment is below 100%, then one should construct more buildings to employ idle peasants.
     *
     * @return float
     */
    public function getEmploymentPercentage(Dominion $dominion): float
    {
        return (float)(min(1, ($this->getPopulationEmployed($dominion) / $dominion->peasants)) * 100);
    }
}
