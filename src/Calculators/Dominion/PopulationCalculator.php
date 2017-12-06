<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\Queue\ConstructionQueueService;
use OpenDominion\Services\Dominion\Queue\TrainingQueueService;

class PopulationCalculator
{
    /** @var BuildingHelper */
    protected $buildingHelper;

    /** @var ConstructionQueueService */
    protected $constructionQueueService;

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
     * @param ConstructionQueueService $constructionQueueService
     * @param ImprovementCalculator $improvementCalculator
     * @param LandCalculator $landCalculator
     * @param SpellCalculator $spellCalculator
     * @param TrainingQueueService $trainingQueueService
     * @param UnitHelper $unitHelper
     */
    public function __construct(
        BuildingHelper $buildingHelper,
        ConstructionQueueService $constructionQueueService,
        ImprovementCalculator $improvementCalculator,
        LandCalculator $landCalculator,
        SpellCalculator $spellCalculator,
        TrainingQueueService $trainingQueueService,
        UnitHelper $unitHelper
    ) {
        $this->buildingHelper = $buildingHelper;
        $this->constructionQueueService = $constructionQueueService;
        $this->improvementCalculator = $improvementCalculator;
        $this->landCalculator = $landCalculator;
        $this->spellCalculator = $spellCalculator;
        $this->trainingQueueService = $trainingQueueService;
        $this->unitHelper = $unitHelper;
    }

    /**
     * Returns the Dominion's total population, both peasants and military.
     *
     * @param Dominion $dominion
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
     * @param Dominion $dominion
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
     * @param Dominion $dominion
     * @return int
     */
    public function getMaxPopulation(Dominion $dominion): int
    {
        return round(
            ($this->getMaxPopulationRaw($dominion) * $this->getMaxPopulationMultiplier($dominion))
            + $this->getMaxPopulationMilitaryBonus($dominion)
        );
    }

    /**
     * Returns the Dominion's raw max population.
     *
     * Maximum population is determined by housing in homes, other buildings (sans barracks) and barren land.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getMaxPopulationRaw(Dominion $dominion): int
    {
        $population = 0;

        // Values
        $housingPerHome = 30;
        $housingPerNonHome = 15; // except barracks
        $housingPerBarracks = 0;
        $housingPerBarrenLand = 5;
        $housingPerConstructingBuilding = 15; // todo: check how many constructing home/barracks houses

        // todo: race bonus for barren land
        // todo: ^ think about what I meant to say here. note to self: be more clear in the future

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
        $population += ($this->constructionQueueService->getQueueTotal($dominion) * $housingPerConstructingBuilding);

        // Barren land
        $population += ($this->landCalculator->getTotalBarrenLand($dominion) * $housingPerBarrenLand);

        return $population;
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
     * @param Dominion $dominion
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

        return (1 + $multiplier);
    }

    /**
     * Returns the Dominion's max population military bonus.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getMaxPopulationMilitaryBonus(Dominion $dominion): float
    {
        // Values
        $troopsPerBarracks = 36;

        return min(
            ($this->getPopulationMilitary($dominion) - $dominion->military_draftees - $this->trainingQueueService->getQueueTotal($dominion)),
            ($dominion->building_barracks * $troopsPerBarracks)
        );
    }

    /**
     * Returns the Dominion's population birth.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPopulationBirth(Dominion $dominion): int
    {
        return round($this->getPopulationBirthRaw($dominion) * $this->getPopulationBirthMultiplier($dominion));
    }

    /**
     * Returns the Dominions raw population birth.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getPopulationBirthRaw(Dominion $dominion): float
    {
        $birth = 0;

        // Values (percentages)
        $growthFactor = 3;

        // Growth
        $birth += (($dominion->peasants - $this->getPopulationDrafteeGrowth($dominion)) * ($growthFactor / 100));

        return $birth;
    }

    /**
     * Returns the Dominion's population birth multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getPopulationBirthMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Values (percentages)
        $spellHarmony = 50;
        $templeBonus = 6;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('population_growth');

        // Spell: Harmony
        $multiplier += $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, 'harmony', $spellHarmony);

        // Temples
        $multiplier += (($dominion->building_temple / $this->landCalculator->getTotalLand($dominion)) * $templeBonus);

        return (1 + $multiplier);
    }

    /**
     * Returns the Dominion's population peasant growth.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPopulationPeasantGrowth(Dominion $dominion): int
    {
        return max(
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
     * @param Dominion $dominion
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

        return $draftees;
    }

    /**
     * Returns the Dominion's population peasant percentage.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getPopulationPeasantPercentage(Dominion $dominion): float
    {
        if (($dominionPopulation = $this->getPopulation($dominion)) === 0) {
            return (float)0;
        }

        return (($dominion->peasants / $dominionPopulation) * 100);
    }

    /**
     * Returns the Dominion's population military percentage.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getPopulationMilitaryPercentage(Dominion $dominion): float
    {
        if (($dominionPopulation = $this->getPopulation($dominion)) === 0) {
            return 0;
        }

        return (($this->getPopulationMilitary($dominion) / $dominionPopulation) * 100);
    }

    /**
     * Returns the Dominion's employment jobs.
     *
     * Each building (sans home and barracks) employs 20 peasants.
     *
     * @param Dominion $dominion
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
     * @param Dominion $dominion
     * @return int
     */
    public function getPopulationEmployed(Dominion $dominion): int
    {
        return min($this->getEmploymentJobs($dominion), $dominion->peasants);
    }

    /**
     * Returns the Dominion's employment percentage.
     *
     * If employment is at or above 100%, then one should strive to build more homes to get more peasants to the working
     * force. If employment is below 100%, then one should construct more buildings to employ idle peasants.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getEmploymentPercentage(Dominion $dominion): float
    {
        return (min(1, ($this->getPopulationEmployed($dominion) / $dominion->peasants)) * 100);
    }
}
