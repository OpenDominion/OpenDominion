<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\UserAchievement
 *
 * @property int $user_id
 * @property int $achievement_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\User $user
 * @property-read \OpenDominion\Models\Achievement $achievement
 */
class UserAchievement extends AbstractModel
{
    protected $table = 'user_achievements';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function achievement()
    {
        return $this->belongsTo(Achievement::class, 'achievement_id');
    }
}
