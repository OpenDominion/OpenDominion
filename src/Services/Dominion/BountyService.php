<?php

namespace OpenDominion\Services\Dominion;

use DB;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Bounty;
use OpenDominion\Models\Dominion;

class BountyService
{
    public const REWARD_DAILY_LIMIT = 24;
    public const REWARD_XP = 4;
    public const REWARD_RESOURCE = 'resource_tech';
    public const REWARD_AMOUNT = 20;

    /**
     * Get bounties for a realm, excluding the selected dominion
     *
     * @param Dominion $dominion
     */
    public function getBounties(Dominion $dominion)
    {
        $activeBounties = Bounty::active()
            ->where('source_realm_id', $dominion->realm_id)
            ->get()
            ->groupBy('target_dominion_id');

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
            ->count();

        return $collectedBounties;
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
            Bounty::insert([
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
     * Set a bounty as collected
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return array
     */
    public function collectBounty(Dominion $dominion, Dominion $target, string $type): array
    {
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

            // Set collected by
            $activeBounty->update([
                'collected_by_dominion_id' => $dominion->id
            ]);
            $dominion->stat_bounties_collected += 1;

            // Check limits
            $bountiesCollected = $this->getBountiesCollected($dominion);
            if ($bountiesCollected < static::REWARD_DAILY_LIMIT) {
                return [
                    'xp' => static::REWARD_XP,
                    'resource' => static::REWARD_RESOURCE,
                    'amount' => static::REWARD_AMOUNT,
                ];
            }
        }

        return [];
    }
}
