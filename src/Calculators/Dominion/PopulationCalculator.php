<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\QueueService;

class PopulationCalculator
{
    /** @var BuildingHelper */
    protected $buildingHelper;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var PrestigeCalculator */
    private $prestigeCalculator;

    /** @var QueueService */
    protected $queueService;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var UnitHelper */
    protected $unitHelper;

    /** @var bool */
    protected $forTick = false;

    /**
     * PopulationCalculator constructor.
     *
     * @param BuildingHelper $buildingHelper
     * @param ImprovementCalculator $improvementCalculator
     * @param LandCalculator $landCalculator
     * @param MilitaryCalculator $militaryCalculator
     * @param PrestigeCalculator $prestigeCalculator
     * @param QueueService $queueService
     * @param SpellCalculator $spellCalculator
     * @param UnitHelper $unitHelper
     */
    public function __construct(
        BuildingHelper $buildingHelper,
        ImprovementCalculator $improvementCalculator,
        LandCalculator $landCalculator,
        MilitaryCalculator $militaryCalculator,
        PrestigeCalculator $prestigeCalculator,
        QueueService $queueService,
        SpellCalculator $spellCalculator,
        UnitHelper $unitHelper
    ) {
        $this->buildingHelper = $buildingHelper;
        $this->improvementCalculator = $improvementCalculator;
        $this->landCalculator = $landCalculator;
        $this->militaryCalculator = $militaryCalculator;
        $this->prestigeCalculator = $prestigeCalculator;
        $this->queueService = $queueService;
        $this->spellCalculator = $spellCalculator;
        $this->unitHelper = $unitHelper;
    }

    /**
     * Toggle if this calculator should include the following hour's resources.
     */
    public function setForTick(bool $value)
    {
        $this->forTick = $value;
        $this->militaryCalculator->setForTick($value);
        $this->queueService->setForTick($value);
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
     * The military consists of draftees, combat units, spies, wizards, archmages and
     * units currently in training.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPopulationMilitary(Dominion $dominion): int
    {
        return (
            $dominion->military_draftees
            + $this->militaryCalculator->getTotalUnitsForSlot($dominion, 1)
            + $this->militaryCalculator->getTotalUnitsForSlot($dominion, 2)
            + $this->militaryCalculator->getTotalUnitsForSlot($dominion, 3)
            + $this->militaryCalculator->getTotalUnitsForSlot($dominion, 4)
            + $dominion->military_spies
            + $dominion->military_wizards
            + $dominion->military_archmages
            + $this->queueService->getTrainingQueueTotal($dominion)
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
        $housingPerBarrenLand = (5 + $dominion->race->getPerkValue('extra_barren_max_population'));
        $housingPerConstructingBuilding = 15; // todo: check how many constructing home/barracks houses

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
        $population += ($this->queueService->getConstructionQueueTotal($dominion) * $housingPerConstructingBuilding);

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
        $prestigeMultiplier = $this->prestigeCalculator->getPrestigeMultiplier($dominion);

        return (1 + $multiplier) * (1 + $prestigeMultiplier);
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
            ($this->getPopulationMilitary($dominion) - $dominion->military_draftees),
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
        $maximumPeasantDeath = ((-0.05 * $dominion->peasants) - $this->getPopulationDrafteeGrowth($dominion));
        $roomForPeasants = ($this->getMaxPopulation($dominion) - $this->getPopulation($dominion) - $this->getPopulationDrafteeGrowth($dominion));
        $currentPopulationChange = ($this->getPopulationBirth($dominion) - $this->getPopulationDrafteeGrowth($dominion));

        $maximumPopulationChange = min($roomForPeasants, $currentPopulationChange);

        return max($maximumPeasantDeath, $maximumPopulationChange);

         /*
        =MAX(
            -5% * peasants - drafteegrowth,
            -5% * peasants - drafteegrowth, // MAX PEASANT DEATH
            MIN(
                maxpop(nexthour) - (peasants - military) - drafteesgrowth,
                moddedbirth - drafteegrowth
                maxpop(nexthour) - (peasants - military) - drafteesgrowth, // MAX SPACE FOR PEASANTS
                moddedbirth - drafteegrowth // CURRENT BIRTH RATE
            )
        )
        */
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
            $draftees += round(($dominion->peasants * ($growthFactor / 100)));
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
        if ($dominion->peasants === 0) {
            return 0;
        }

        return (min(1, ($this->getPopulationEmployed($dominion) / $dominion->peasants)) * 100);
    }
}
