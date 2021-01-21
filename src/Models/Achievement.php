<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\Achievement
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $icon
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\User[] $users
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Achievement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Achievement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Achievement query()
 * @mixin \Eloquent
 */
class Achievement extends AbstractModel
{
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'user_achievements',
            'achievement_id',
            'user_id'
        );
    }
}
