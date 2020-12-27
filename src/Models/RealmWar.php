<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Builder;
use OpenDominion\Services\Realm\HistoryService;

/**
 * OpenDominion\Models\RealmWar
 *
 * @property int $id
 * @property int $source_realm_id
 * @property string|null $source_realm_name
 * @property int $target_realm_id
 * @property string|null $target_realm_name
 * @property \Illuminate\Support\Carbon|null $active_at
 * @property \Illuminate\Support\Carbon|null $inactive_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Realm $sourceRealm
 * @property-read \OpenDominion\Models\Realm $targetRealm
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Realm newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Realm newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Realm query()
 * @mixin \Eloquent
 */
class RealmWar extends AbstractModel
{
    protected $casts = [
        'active_at' => 'datetime',
        'inactive_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function sourceRealm()
    {
        return $this->belongsTo(Realm::class);
    }

    public function targetRealm()
    {
        return $this->belongsTo(Realm::class);
    }

    // Eloquent Query Scopes

    public function scopeEngaged(Builder $query): Builder
    {
        return $query->where('inactive_at', null);
    }

    public function scopeEscalated(Builder $query): Builder
    {
        return $query->where('active_at', '<', now())->where(function ($query) {
            $query->where('inactive_at', null)->orWhere('inactive_at', '<', now());
        });
    }

    public function scopeCanceled(Builder $query): Builder
    {
        return $query->where('inactive_at', '!=', null)->where('inactive_at', '<', now());
    }
}
