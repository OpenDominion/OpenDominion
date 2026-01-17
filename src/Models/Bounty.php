<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * OpenDominion\Models\Bounty
 *
 * @property int $id
 * @property int $round_id
 * @property int $source_realm_id
 * @property int $source_dominion_id
 * @property int $target_dominion_id
 * @property int|null $collected_by_dominion_id
 * @property string $type
 * @property bool $reward
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Round $round
 * @property-read \OpenDominion\Models\Realm $sourceRealm
 * @property-read \OpenDominion\Models\Dominion $sourceDominion
 * @property-read \OpenDominion\Models\Dominion $targetDominion
 * @property-read \OpenDominion\Models\Dominion $collectedByDominion
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Bounty newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Bounty newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Bounty query()
 * @mixin \Eloquent
 */
class Bounty extends AbstractModel
{
    protected $table = 'bounties';

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function sourceRealm()
    {
        return $this->belongsTo(Realm::class, 'source_realm_id');
    }

    public function sourceDominion()
    {
        return $this->hasOne(Dominion::class, 'id', 'source_dominion_id');
    }

    public function targetDominion()
    {
        return $this->hasOne(Dominion::class, 'id', 'target_dominion_id');
    }

    public function collectedByDominion()
    {
        return $this->hasOne(Dominion::class, 'id', 'collected_by_dominion_id');
    }

    public function getLatestInfoOp()
    {
        return InfoOp::query()
            ->where('target_dominion_id', $this->target_dominion_id)
            ->where('source_realm_id', $this->source_realm_id)
            ->where('type', $this->type)
            ->where('latest', true)
            ->first();
    }

    // Eloquent Query Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('collected_by_dominion_id', null)
            ->where('created_at', '>', now()->subHours(12));
    }
}
