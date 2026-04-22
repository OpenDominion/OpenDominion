<?php

namespace OpenDominion\Services;

use Illuminate\Support\Collection;
use OpenDominion\Models\Achievement;
use OpenDominion\Models\User;
use OpenDominion\Models\UserAchievement;

class AchievementService
{
    /**
     * Unlock an achievement for a user. Idempotent.
     *
     * @param User $user
     * @param string $achievementKey
     * @return bool True if newly unlocked, false if already held
     */
    public function unlock(User $user, string $achievementKey): bool
    {
        $achievement = Achievement::where('key', $achievementKey)->first();

        if ($achievement === null) {
            return false;
        }

        $existing = UserAchievement::where('user_id', $user->id)
            ->where('achievement_id', $achievement->id)
            ->exists();

        if ($existing) {
            return false;
        }

        UserAchievement::create([
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
        ]);

        return true;
    }

    /**
     * Check if a user has a specific achievement.
     *
     * @param User $user
     * @param string $achievementKey
     * @return bool
     */
    public function hasAchievement(User $user, string $achievementKey): bool
    {
        return $user->achievements()
            ->where('key', $achievementKey)
            ->exists();
    }
}
