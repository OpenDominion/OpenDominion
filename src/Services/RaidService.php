<?php

namespace OpenDominion\Services;

use DB;
use Illuminate\Support\Collection;
use OpenDominion\Calculators\RaidCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Raid;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\NotificationService;

class RaidService
{
    /** @var RaidCalculator */
    protected $raidCalculator;

    /** @var HistoryService */
    protected $historyService;

    /** @var NotificationService */
    protected $notificationService;

    /** @var array */
    protected $rewardData = [];

    public function __construct(RaidCalculator $raidCalculator, HistoryService $historyService, NotificationService $notificationService)
    {
        $this->raidCalculator = $raidCalculator;
        $this->historyService = $historyService;
        $this->notificationService = $notificationService;
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
            $this->rewardData = $this->raidCalculator->calculateRaidRewards($raid);

            foreach ($this->rewardData as $data) {
                $this->distributeRewardToDominion(
                    $data['dominion'],
                    $data['participation_reward'],
                    $data['completion_reward']
                );
            }

            // Mark raid as having rewards distributed
            $raid->update(['rewards_distributed' => true]);
        });

        foreach ($this->rewardData as $data) {
            // Queue notification for this dominion
            $this->notificationService->queueNotification('raid_rewards', [
                'raid_name' => $raid->name,
                'participation_amount' => $data['participation_reward']['amount'],
                'participation_resource' => $data['participation_reward']['resource'],
                'completion_amount' => $data['completion_reward']['amount'],
                'completion_resource' => $data['completion_reward']['resource'],
            ]);

            // Send notification to dominion
            $this->notificationService->sendNotifications($data['dominion'], 'irregular_dominion');
        }

    }

    /**
     * Distribute rewards to a specific dominion.
     */
    protected function distributeRewardToDominion(Dominion $dominion, array $participationReward, array $completionReward): void
    {
        // Add participation reward
        if ($participationReward['amount'] > 0) {
            $resourceField = "{$participationReward['resource']}";
            $dominion->$resourceField += $participationReward['amount'];
        }

        // Add completion reward
        if ($completionReward['amount'] > 0) {
            $resourceField = "{$completionReward['resource']}";
            $dominion->$resourceField += $completionReward['amount'];
        }

        // Save changes and record history
        $dominion->save(['event' => HistoryService::EVENT_ACTION_RAID_REWARD]);
    }
}
