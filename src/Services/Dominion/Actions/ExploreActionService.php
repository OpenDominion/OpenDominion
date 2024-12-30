<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use OpenDominion\Calculators\Dominion\Actions\ExplorationCalculator;
use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\AIHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Traits\DominionGuardsTrait;

class ExploreActionService
{
    use DominionGuardsTrait;

    /**
     * @var float XP gain per acre explored
     */
    protected const XP_PER_ACRE = 0.25;

    /** @var ExplorationCalculator */
    protected $explorationCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var LandHelper */
    protected $landHelper;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var ProtectionService */
    protected $protectionService;

    /** @var QueueService */
    protected $queueService;

    /**
     * ExplorationActionService constructor.
     */
    public function __construct()
    {
        $this->explorationCalculator = app(ExplorationCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->landHelper = app(LandHelper::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->protectionService = app(ProtectionService::class);
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
        $this->guardActionsDuringTick($dominion);

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

        $landTotal = $this->landCalculator->getTotalLand($dominion);

        if ($dominion->user_id !== null && !$this->protectionService->isUnderProtection($dominion)) {
            $incomingLand = $this->queueService->getExplorationQueueTotal($dominion);
            if (($totalLandToExplore + $incomingLand) > ($landTotal / 2)) {
                throw new GameException('You cannot explore for more than 50% of your current land total.');
            }

            $incomingLand += $this->queueService->getInvasionQueue($dominion)->filter(function ($queue) {
                if (substr($queue->resource, 0, 4) == 'land') {
                    return true;
                }
            })->pluck('amount')->sum();

            $newLandTotal = $totalLandToExplore + $incomingLand + $landTotal;
            $minimumDefense = $this->militaryCalculator->getMinimumDefense(null, $newLandTotal);

            // Queues
            $incomingQueue = DB::table('dominion_queue')
                ->where('dominion_id', $dominion->id)
                ->get();

            foreach ($incomingQueue as $row) {
                // Temporarily add incoming resources for accurate calculations
                $dominion->{$row->resource} += $row->amount;
            }

            // Calculate defense with incomings
            $defensivePower = $this->militaryCalculator->getDefensivePower($dominion);

            foreach ($incomingQueue as $row) {
                // Reset current resources in case object is saved later
                $dominion->{$row->resource} -= $row->amount;
            }

            $aiHelper = app(AIHelper::class);
            $botMaxSize = $aiHelper->getExpectedLandSize($dominion->round);
            if ($landTotal < max(600, $botMaxSize)) {
                $minimumDefense = round($aiHelper->getDefenseForNonPlayer($dominion->round, max(600, $newLandTotal)) * 0.90);
            }

            if ($defensivePower <= $minimumDefense) {
                throw new GameException(sprintf('Your military refuses to explore any new land with less than %s defense.', $minimumDefense));
            }
        }

        // todo: refactor. see training action service. same with other action services
        $moraleDrop = min($dominion->morale, $this->explorationCalculator->getMoraleDrop($dominion, $totalLandToExplore));
        $platinumCost = ($this->explorationCalculator->getPlatinumCost($dominion) * $totalLandToExplore);
        $drafteeCost = ($this->explorationCalculator->getDrafteeCost($dominion) * $totalLandToExplore);

        DB::transaction(function () use ($dominion, $data, $moraleDrop, $platinumCost, $drafteeCost, $totalLandToExplore) {
            $this->queueService->queueResources('exploration', $dominion, $data);

            $dominion->stat_total_land_explored += $totalLandToExplore;
            $dominion->stat_total_platinum_spent_exploration += $platinumCost;
            $dominion->fill([
                'morale' => ($dominion->morale - $moraleDrop),
                'resource_platinum' => ($dominion->resource_platinum - $platinumCost),
                'military_draftees' => ($dominion->military_draftees - $drafteeCost),
            ])->save([
                'event' => HistoryService::EVENT_ACTION_EXPLORE,
                'queue' => ['exploration' => array_filter($data)]
            ]);

            // Hero Experience
            if ($dominion->hero) {
                $heroCalculator = app(HeroCalculator::class);
                $xpGain = $heroCalculator->getExperienceGain($dominion, $totalLandToExplore * static::XP_PER_ACRE);
                $dominion->hero->experience += $xpGain;
                $dominion->hero->save();
            }
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
