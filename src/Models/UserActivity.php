<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\UserActivity
 *
 * @property int $id
 * @property int $user_id
 * @property string $ip
 * @property string|null $status
 * @property string|null $device
 * @property string $key
 * @property array|null $context
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property-read \OpenDominion\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\UserActivity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\UserActivity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\UserActivity query()
 * @mixin \Eloquent
 */
class UserActivity extends AbstractModel // todo: AbstractReadOnlyModel
{
    protected $guarded = ['id', 'created_at'];

    protected $dates = ['created_at'];

    protected $casts = [
        'context' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function setUpdatedAt($value)
    {
        return $this;
    }
}
