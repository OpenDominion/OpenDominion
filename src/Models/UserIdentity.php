<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\UserIdentity
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $fingerprint
 * @property string|null $user_agent
 * @property int $count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\UserIdentity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\UserIdentity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\UserIdentity query()
 * @mixin \Eloquent
 */
class UserIdentity extends AbstractModel
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
