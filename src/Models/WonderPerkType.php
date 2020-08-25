<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\WonderPerkType
 *
 * @property int $id
 * @property string $key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Wonder[] $wonders
 */
class WonderPerkType extends AbstractModel
{
    public function wonders()
    {
        return $this->belongsToMany(
            Wonder::class,
            'wonder_perks',
            'wonder_perk_type_id',
            'wonder_id'
        )
            ->withTimestamps();
    }
}
