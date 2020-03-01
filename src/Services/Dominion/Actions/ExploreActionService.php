<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use OpenDominion\Calculators\Dominion\Actions\ExplorationCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Traits\DominionGuardsTrait;

class ExploreActionService
{
    use DominionGuardsTrait;

    /** @var ExplorationCalculator */
    protected $explorationCalculator;

    /** @var LandHelper */
    protected $landHelper;

    /** @var QueueService */
    protected $queueService;

    /**
     * ExplorationActionService constructor.
     */
    public function __construct()
    {
        $this->explorationCalculator = app(ExplorationCalculator::class);
        $this->landHelper = app(LandHelper::class);
        $this->queueService = app(QueueService::class);
    }

    /**
     * Does an explore action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $data
     * @return array
     * @throws GameException
     */
    public function explore(Dominion $dominion, array $data): array
    {
        $this->guardLockedDominion($dominion);

        if($dominion->round->hasOffensiveActionsDisabled())
        {
            throw new GameException('Exploration has been disabled for the remainder of the round.');
        }

        $data = array_only($data, array_map(function ($value) {
            return "land_{$value}";
        }, $this->landHelper->getLandTypes()));

        $data = array_map('\intval', $data);

        $totalLandToExplore = array_sum($data);

        if ($totalLandToExplore <= 0) {
            throw new GameException('Exploration was not begun due to bad input.');
        }

        foreach($data as $amount) {
            if ($amount < 0) {
                throw new GameException('Exploration was not completed due to bad input.');
            }
        }

        $maxAfford = $this->explorationCalculator->getMaxAfford($dominion);

        if ($totalLandToExplore > $maxAfford) {
            throw new GameException("You do not have enough platinum and/or draftees to explore for {$totalLandToExplore} acres.");
        }

        // todo: refactor. see training action service. same with other action services
        $moraleDrop = max(0, ($dominion->morale - $this->explorationCalculator->getMoraleDrop($totalLandToExplore)));
        $platinumCost = ($this->explorationCalculator->getPlatinumCost($dominion) * $totalLandToExplore);
        $drafteeCost = ($this->explorationCalculator->getDrafteeCost($dominion) * $totalLandToExplore);

        DB::transaction(function () use ($dominion, $data, $moraleDrop, $platinumCost, $drafteeCost, $totalLandToExplore) {
            // Refresh in transaction to prevent race condition
            $dominion->refresh();
            $this->queueService->queueResources('exploration', $dominion, $data);

            $dominion->stat_total_land_explored += $totalLandToExplore;
            $dominion->fill([
                'morale' => ($dominion->morale - $moraleDrop),
                'resource_platinum' => ($dominion->resource_platinum - $platinumCost),
                'military_draftees' => ($dominion->military_draftees - $drafteeCost),
            ])->save(['event' => HistoryService::EVENT_ACTION_EXPLORE]);
        });

        return [
            'message' => sprintf(
                'Exploration begun at a cost of %s platinum and %s %s. Your orders for exploration disheartens the military, and morale drops %d%%.',
                number_format($platinumCost),
                number_format($drafteeCost),
                str_plural('draftee', $drafteeCost),
                $moraleDrop
            ),
            'data' => [
                'platinumCost' => $platinumCost,
                'drafteeCost' => $drafteeCost,
                'moraleDrop' => $moraleDrop,
            ]
        ];
    }
}
