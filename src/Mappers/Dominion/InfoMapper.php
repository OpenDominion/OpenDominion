<?php

namespace OpenDominion\Mappers\Dominion;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\QueueService;

class InfoMapper
{
    /** @var LandCalculator */
    protected $landCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var NetworthCalculator */
    protected $networthCalculator;

    /** @var PopulationCalculator */
    protected $populationCalculator;

    /** @var QueueService */
    protected $queueService;

    public function __construct()
    {
        $this->landCalculator = app(LandCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->networthCalculator = app(NetworthCalculator::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->queueService = app(QueueService::class);
    }

    public function mapStatus(Dominion $target, bool $isOp = true): array
    {
        $data = [
            'ruler_name' => $target->ruler_name,
            'race_id' => $target->race->id,
            'land' => $this->landCalculator->getTotalLand($target),
            'peasants' => $target->peasants,
            'employment' => $this->populationCalculator->getEmploymentPercentage($target),
            'networth' => $this->networthCalculator->getDominionNetworth($target),
            'prestige' => $target->prestige,

            'resource_platinum' => $target->resource_platinum,
            'resource_food' => $target->resource_food,
            'resource_lumber' => $target->resource_lumber,
            'resource_mana' => $target->resource_mana,
            'resource_ore' => $target->resource_ore,
            'resource_gems' => $target->resource_gems,
            'resource_tech' => $target->resource_tech,
            'resource_boats' => $target->resource_boats + $this->queueService->getInvasionQueueTotalByResource(
                    $target,
                    'resource_boats'
                ),

            'morale' => $target->morale,
            'military_draftees' => $target->military_draftees,
            'military_unit1' => $this->militaryCalculator->getTotalUnitsForSlot($target, 1),
            'military_unit2' => $this->militaryCalculator->getTotalUnitsForSlot($target, 2),
            'military_unit3' => $this->militaryCalculator->getTotalUnitsForSlot($target, 3),
            'military_unit4' => $this->militaryCalculator->getTotalUnitsForSlot($target, 4),
            'military_spies' => null,
            'military_wizards' => null,
            'military_archmages' => null,

            'recently_invaded_count' => null,
        ];

        if(!$isOp) {
            $data['military_spies'] = $target->military_spies;
            $data['military_wizards'] = $target->military_wizards;
            $data['military_archmages'] = $target->military_archmages;
        } else {
            $data['recently_invaded_count'] = $this->militaryCalculator->getRecentlyInvadedCount($target);
        }

        return $data;
    }
}