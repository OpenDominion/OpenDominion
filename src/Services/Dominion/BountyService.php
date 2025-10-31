<?php

namespace OpenDominion\Services\Dominion;

use DB;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Bounty;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\InfoOp;
use OpenDominion\Models\Realm;

class BountyService
{
    public const DAILY_RP_LIMIT = 8;
    public const DAILY_XP_LIMIT = 8;
    public const REWARD_RESOURCE = 'resource_tech';
    public const REWARD_AMOUNT = 10;
    public const XP_AMOUNT = 6;

    /**
     * Get bounties for a realm
     *
     * @param Realm $realm
     */
    public function getBounties(Realm $realm)
    {
        $observeDominionIds = $realm->getSetting('observeDominionIds') ?? [];

        $activeBounties = Bounty::active()
            ->with('sourceDominion')
            ->where('source_realm_id', $realm->id)
            ->get()
            ->groupBy('target_dominion_id')
            ->map(function ($bounties) {
                return $bounties->keyBy('type');
            });

        $recentlyBountied = Dominion::with('race', 'realm')
            ->whereExists(function ($query) use ($realm) {
                $query->select(DB::raw(1))
                    ->from('bounties')
                    ->whereColumn('bounties.target_dominion_id', 'dominions.id')
                    ->where('source_realm_id', $realm->id)
                    ->where('created_at', '>', now()->subHours(24));
            })
            ->orWhereIn('id', $observeDominionIds)
            ->get();

        $recentInfoOps = InfoOp::query()
            ->where('latest', true)
            ->where('source_realm_id', $realm->id)
            ->whereIn('target_dominion_id', $recentlyBountied->pluck('id'))
            ->get()
            ->groupBy('target_dominion_id')
            ->map(function ($infoOps) {
                return $infoOps->sortByDesc('created_at')->keyBy('type');
            });

        return $recentlyBountied->map(function ($dominion) use ($activeBounties, $recentInfoOps) {
            $dominion->bounties = collect();
            $dominion->info_ops = collect();
            $dominion->latest_info_at = now()->subDays(24);
            $dominion->active = false;

            if (isset($activeBounties[$dominion->id])) {
                $dominion->bounties = $activeBounties[$dominion->id];
            }
            if (isset($recentInfoOps[$dominion->id])) {
                $dominion->info_ops = $recentInfoOps[$dominion->id];
                $dominion->latest_info_at = $dominion->info_ops->first()->created_at;
            }
            if (!$dominion->bounties->isEmpty()) {
                $dominion->active = true;
            }

            return $dominion;
        })->sortByDesc('latest_info_at');
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
        if (!$dominion->round->hasStarted()) {
            throw new GameException('You cannot post bounties before the round has started.');
        }

        if ($dominion->realm_id == $target->realm_id) {
            throw new GameException('You cannot post bounties against your own realm.');
        }

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
        $deleteBounties = Bounty::active()
            ->where('target_dominion_id', $target->id)
            ->where('type', $type);

        if (!($dominion->isMonarch() || $dominion->isSpymaster())) {
            $deleteBounties = $deleteBounties->where('source_dominion_id', $dominion->id);
        }

        $bountiesDeleted = $deleteBounties->delete();

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
            ->where('target_dominion_id', $target->id)
            ->where('type', $type)
            ->get()
            ->keyBy('id');

        $observeDominionIds = $dominion->realm->getSetting('observeDominionIds') ?? [];
        $isMarked = in_array($target->id, $observeDominionIds);

        $activeBounty = $activeBounties->first();
        // Create placeholder bounty
        if ($activeBounty == null && $isMarked) {
            $activeBounty = new Bounty([
                'round_id' => $dominion->round_id,
                'source_realm_id' => $dominion->realm->id,
                'source_dominion_id' => $dominion->id,
                'target_dominion_id' => $target->id,
                'type' => $type
            ]);
        }

        if ($activeBounty !== null) {
            // Delete when collecting own bounty
            if (!$isMarked && ($activeBounty->source_dominion_id == $dominion->id)) {
                $activeBounty->delete();
                return $bountyRewards;
            }

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

                if (!($latestOp && !$latestOp->isStale())) {
                    if ($bountiesCollected < static::DAILY_RP_LIMIT) {
                        $bountyRewards = [
                            'resource' => static::REWARD_RESOURCE,
                            'amount' => static::REWARD_AMOUNT,
                            'xp' => static::XP_AMOUNT
                        ];
                    } elseif ($bountiesCollected < static::DAILY_XP_LIMIT) {
                        $bountyRewards = [
                            'xp' => static::XP_AMOUNT
                        ];
                    } else {
                        $bountyRewards = [
                            'xp' => 0
                        ];
                    }
                    if ($activeBounty->id == null) {
                        $activeBounty->save();
                    }
                }
            }

            // Set collected by
            if ($activeBounty->id !== null) {
                $activeBounty->update([
                    'collected_by_dominion_id' => $dominion->id,
                    'reward' => !empty($bountyRewards)
                ]);
                $dominion->stat_bounties_collected += 1;
            }
        }

        return $bountyRewards;
    }

    /**
     * Toggle realm observation for a dominion
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @return array
     */
    public function toggleObservation(Dominion $dominion, Dominion $target)
    {
        if (!$dominion->round->hasStarted()) {
            throw new GameException('You cannot observe dominions before the round has started.');
        }

        if ($dominion->realm_id == $target->realm_id) {
            throw new GameException('You cannot observe dominions in your own realm.');
        }

        if (!($dominion->isMonarch() || $dominion->isSpymaster())) {
            throw new GameException('Only the monarch or spymaster can mark dominions for observation.');
        }

        $realm = $dominion->realm;
        $settings = ($realm->settings ?? []);
        $dominionIds = $realm->getSetting('observeDominionIds') ?? [];
        if (in_array($target->id, $dominionIds)) {
            array_splice($dominionIds, array_search($target->id, $dominionIds), 1);
            $result = [
                'message' => 'Target removed from observation.',
                'alert-type' => 'success'
            ];
        } else {
            if (count($dominionIds) >= 15) {
                throw new GameException('Only 15 dominions can be marked for observation at a time.');
            }
            $dominionIds[] = $target->id;
            $result = [
                'message' => 'Target marked for observation.',
                'alert-type' => 'success'
            ];
        }
        $settings['observeDominionIds'] = $dominionIds;
        $realm->settings = $settings;
        $realm->save();

        return $result;
    }
}
