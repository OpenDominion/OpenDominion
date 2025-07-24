<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\UserFeedback
 *
 * @property int $id
 * @property int $source_id
 * @property int $target_id
 * @property int $round_id
 * @property bool $endorsed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\User $source
 * @property-read \OpenDominion\Models\User $target
 * @property-read \OpenDominion\Models\Round $round
 */
class UserFeedback extends AbstractModel
{
    protected $guarded = ['id', 'created_at'];

    protected $dates = ['created_at', 'updated_at'];

    protected $casts = [
        'endorsed' => 'boolean'
    ];

    public function source()
    {
        return $this->hasOne(User::class);
    }

    public function target()
    {
        return $this->hasOne(User::class);
    }

    public function round()
    {
        return $this->hasOne(Round::class);
    }
}
