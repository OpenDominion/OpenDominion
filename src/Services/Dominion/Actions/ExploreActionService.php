<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use OpenDominion\Calculators\Dominion\Actions\ExplorationCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;
use Throwable;

class ExploreActionService
{
    use DominionGuardsTrait;

    /** @var ExplorationCalculator */
    protected $explorationCalculator;

    /** @var QueueService */
    protected $queueService;

    /**
     * ExplorationActionService constructor.
     *
     * @param ExplorationCalculator $explorationCalculator
     * @param QueueService $queueService
     */
    public function __construct(ExplorationCalculator $explorationCalculator, QueueService $queueService)
    {
        $this->explorationCalculator = $explorationCalculator;
        $this->queueService = $queueService;
    }

    /**
     * Does an explore action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $data
     * @return array
     * @throws Throwable
     */
    public function explore(Dominion $dominion, array $data): array
    {
        $this->guardLockedDominion($dominion);

        $data = array_map('\intval', $data);

        $totalLandToExplore = array_sum($data);

        if ($totalLandToExplore === 0) {
            throw new RuntimeException('Exploration was not begun due to bad input.');
        }

        $maxAfford = $this->explorationCalculator->getMaxAfford($dominion);

        if ($totalLandToExplore > $maxAfford) {
            throw new RuntimeException("You do not have enough platinum and/or draftees to explore for {$totalLandToExplore} acres.");
        }

        // todo: refactor. see training action service. same with other action services
        $newMorale = max(0, ($dominion->morale - $this->explorationCalculator->getMoraleDrop($totalLandToExplore)));
        $moraleDrop = ($dominion->morale - $newMorale);

        $platinumCost = ($this->explorationCalculator->getPlatinumCost($dominion) * $totalLandToExplore);
        $newPlatinum = ($dominion->resource_platinum - $platinumCost);

        $drafteeCost = ($this->explorationCalculator->getDrafteeCost($dominion) * $totalLandToExplore);
        $newDraftees = ($dominion->military_draftees - $drafteeCost);

        DB::transaction(function () use ($dominion, $data, $newMorale, $newPlatinum, $newDraftees) {
            $dominion->fill([
                'morale' => $newMorale,
                'resource_platinum' => $newPlatinum,
                'military_draftees' => $newDraftees,
            ])->save(['event' => HistoryService::EVENT_ACTION_EXPLORE]);

            $this->queueService->queueResources('exploration', $dominion, $data);
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
