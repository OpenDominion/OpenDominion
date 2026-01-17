<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\UserOrigin
 *
 * @property int $id
 * @property int $user_id
 * @property int $dominion_id
 * @property string $ip_address
 * @property int $count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Dominion $dominion
 * @property-read \OpenDominion\Models\User $user
 * @property-read \OpenDominion\Models\UserOriginLookup $lookup
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\UserOrigin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\UserOrigin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\UserOrigin query()
 * @mixin \Eloquent
 */
class UserOrigin extends AbstractModel
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function dominion()
    {
        return $this->belongsTo(Dominion::class);
    }

    public function lookup()
    {
        return $this->belongsTo(UserOriginLookup::class, 'ip_address', 'ip_address');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
