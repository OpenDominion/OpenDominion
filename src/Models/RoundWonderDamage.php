<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\RoundWonderDamage
 *
 * @property int $round_wonder_id
 * @property int $realm_id
 * @property int $dominion_id
 * @property int $damage
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Round $wonder
 * @property-read \OpenDominion\Models\Realm $realm
 * @property-read \OpenDominion\Models\Wonder $dominion
 */
class RoundWonderDamage extends AbstractModel
{
    protected $table = 'round_wonder_damage';

    public function wonder()
    {
        return $this->belongsTo(RoundWonder::class, 'round_wonder_id');
    }

    public function realm()
    {
        return $this->belongsTo(Realm::class);
    }

    public function dominion()
    {
        return $this->belongsTo(Dominion::class);
    }
}
