<?php

namespace OpenDominion\Services;

use DB;
use Illuminate\Support\Collection;
use OpenDominion\Calculators\RaidCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Raid;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\HistoryService;

class RaidService
{
    /** @var RaidCalculator */
    protected $raidCalculator;

    /** @var HistoryService */
    protected $historyService;

    public function __construct(RaidCalculator $raidCalculator, HistoryService $historyService)
    {
        $this->raidCalculator = $raidCalculator;
        $this->historyService = $historyService;
    }

    /**
     * Process completed raids and distribute rewards.
     */
    public function processCompletedRaids(Round $round): void
    {
        $completedRaids = $round->raids()
            ->with(['objectives'])
            ->where('end_date', '<=', now())
            ->where('rewards_distributed', false)
            ->get();

        foreach ($completedRaids as $raid) {
            $this->distributeRaidRewards($raid);
        }
    }

    /**
     * Distribute rewards for a completed raid.
     */
    public function distributeRaidRewards(Raid $raid): void
    {
        DB::transaction(function () use ($raid) {
            $rewardData = $this->raidCalculator->calculateRaidRewards($raid);

            foreach ($rewardData as $data) {
                $this->distributeRewardToDominion(
                    $data['dominion'],
                    $data['participation_reward'],
                    $data['completion_reward']
                );
            }

            // Mark raid as having rewards distributed
            $raid->update(['rewards_distributed' => true]);
        });
    }

    /**
     * Distribute rewards to a specific dominion.
     */
    protected function distributeRewardToDominion(Dominion $dominion, array $participationReward, array $completionReward): void
    {
        // Add participation reward
        if ($participationReward['amount'] > 0) {
            $resourceField = "resource_{$participationReward['resource']}";
            $dominion->$resourceField += $participationReward['amount'];
        }

        // Add completion reward
        if ($completionReward['amount'] > 0) {
            $resourceField = "resource_{$completionReward['resource']}";
            $dominion->$resourceField += $completionReward['amount'];
        }

        // Save changes and record history
        $dominion->save(['event' => HistoryService::EVENT_ACTION_RAID_REWARD]);
    }
}
