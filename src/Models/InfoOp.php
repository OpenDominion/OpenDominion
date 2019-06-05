<?php

namespace OpenDominion\Models;

use Carbon\Carbon;

/**
 * OpenDominion\Models\InfoOp
 *
 * @property int $id
 * @property int $source_realm_id
 * @property int $source_dominion_id
 * @property int $target_dominion_id
 * @property string $type
 * @property array $data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Dominion $sourceDominion
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\InfoOp newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\InfoOp newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\InfoOp query()
 * @mixin \Eloquent
 */
class InfoOp extends AbstractModel
{
    protected $casts = [
        'source_realm_id' => 'int',
        'source_dominion_id' => 'int',
        'target_dominion_id' => 'int',
        'data' => 'array',
    ];

    public function sourceRealm()
    {
//        return $this->belongsTo(Realm::class);
    }

    public function sourceDominion()
    {
        return $this->belongsTo(Dominion::class, 'source_dominion_id');
    }

    public function targetDominion()
    {
        //
    }

//    public function scopeNotInvalid(Builder $query): Builder
//    {
//        return $query->where('updated_at', '>=', now()->parse('-12 hours')->toDateTimeString());
//    }
//
//    public function scopeTargetDominion(Builder $query, Dominion $target): Builder
//    {
//        return $query->where('target_dominion_id', $target->id);
//    }

    public function isStale(): bool
    {
        return ($this->updated_at < carbon()->minute(0)->second(0));
    }

    public function isInvalid(): bool
    {
        return ($this->updated_at < new Carbon('-12 hours'));
    }
}
