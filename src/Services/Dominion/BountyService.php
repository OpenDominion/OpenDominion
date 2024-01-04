<?php

namespace OpenDominion\Services\Dominion;

use DB;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Bounty;
use OpenDominion\Models\Dominion;

class BountyService
{
    public const DAILY_LIMIT = 12;
    public const REWARD_RESOURCE = 'resource_tech';
    public const REWARD_AMOUNT = 10;

    /**
     * Get bounties for a realm, excluding the selected dominion
     *
     * @param Dominion $dominion
     */
    public function getBounties(Dominion $dominion)
    {
        $activeBounties = Bounty::active()
            ->with(['sourceDominion', 'targetDominion'])
            ->where('source_realm_id', $dominion->realm_id)
            ->get()
            ->groupBy('target_dominion_id')
            ->map(function ($bounties) {
                return $bounties->keyBy('type');
            });

        return $activeBounties;
    }

    /**
     * Get count of bounties collected by a dominion
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getBountiesCollected(Dominion $dominion, int $days = 1)
    {
        $currentDay = $dominion->round->daysInRound();
        $startDate = $dominion->round->start_date->copy()->addDays($currentDay - $days);
        $collectedBounties = Bounty::query()
            ->where('collected_by_dominion_id', $dominion->id)
            ->where('updated_at', '>', $startDate)
            ->where('reward', true)
            ->count();

        return min(static::DAILY_LIMIT, $collectedBounties);
    }

    /**
     * Create a bounty if it doesn't exist
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return array
     */
    public function createBounty(Dominion $dominion, Dominion $target, string $type): array
    {
        $bountiesCreated = Bounty::active()
            ->where('source_dominion_id', $dominion->id)
            ->count();

        if ($bountiesCreated >= 40) {
            throw new GameException('You can only have 40 active bounties at a time.');
        }

        $activeBounties = Bounty::active()
            ->where('source_realm_id', $dominion->realm_id)
            ->where('target_dominion_id', $target->id)
            ->where('type', $type)
            ->get();

        if ($activeBounties->isEmpty()) {
            Bounty::create([
                'round_id' => $dominion->round_id,
                'source_realm_id' => $dominion->realm_id,
                'source_dominion_id' => $dominion->id,
                'target_dominion_id' => $target->id,
                'type' => $type,
            ]);

            return [
                'message' => 'Bounty successfully posted.',
                'alert-type' => 'success'
            ];
        }

        return [
            'message' => 'Bounty already exists.',
            'alert-type' => 'danger'
        ];
    }

    /**
     * Delete a bounty
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return array
     */
    public function deleteBounty(Dominion $dominion, Dominion $target, string $type): array
    {
        $bountiesDeleted = Bounty::active()
            ->where('source_dominion_id', $dominion->id)
            ->where('target_dominion_id', $target->id)
            ->where('type', $type)
            ->delete();

        if ($bountiesDeleted) {
            return [
                'message' => 'Bounty successfully deleted.',
                'alert-type' => 'success'
            ];
        }

        return [
            'message' => 'No valid bounty exists.',
            'alert-type' => 'danger'
        ];
    }

    /**
     * Set a bounty as collected
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return array
     */
    public function collectBounty(Dominion $dominion, Dominion $target, string $type): array
    {
        $bountyRewards = [];

        $activeBounties = Bounty::active()
            ->where('source_realm_id', $dominion->realm_id)
            ->where('source_dominion_id', '!=', $dominion->id)
            ->where('target_dominion_id', $target->id)
            ->where('type', $type)
            ->get()
            ->keyBy('id');

        if (!$activeBounties->isEmpty()) {
            $activeBounty = $activeBounties->first();

            // Delete any duplicates
            $activeBounties->forget($activeBounty->id);
            if (!$activeBounties->isEmpty()) {
                Bounty::whereIn('id', $activeBounties->pluck('id'))->delete();
            }

            // Bounty rewards
            if ($target->user_id !== null) {
                // Check eligibility
                $latestOp = $activeBounty->getLatestInfoOp();

                // Check limits
                $bountiesCollected = $this->getBountiesCollected($dominion);

                if (!($latestOp && !$latestOp->isStale()) && $bountiesCollected < static::DAILY_LIMIT) {
                    $bountyRewards = [
                        'resource' => static::REWARD_RESOURCE,
                        'amount' => static::REWARD_AMOUNT,
                    ];
                }
            }

            // Set collected by
            $activeBounty->update([
                'collected_by_dominion_id' => $dominion->id,
                'reward' => !empty($bountyRewards)
            ]);
            $dominion->stat_bounties_collected += 1;
        }

        return $bountyRewards;
    }
}
