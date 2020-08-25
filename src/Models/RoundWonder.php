<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\RoundWonder
 *
 * @property int $round_id
 * @property int $realm_id
 * @property int $wonder_id
 * @property int $power
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Round $round
 * @property-read \OpenDominion\Models\Realm $realm
 * @property-read \OpenDominion\Models\Wonder $wonder
 */
class RoundWonder extends AbstractModel
{
    protected $table = 'round_wonders';

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function realm()
    {
        return $this->belongsTo(Realm::class);
    }

    public function wonder()
    {
        return $this->belongsTo(Wonder::class);
    }

    public function damage()
    {
        return $this->hasMany(RoundWonderDamage::class);
    }
}
