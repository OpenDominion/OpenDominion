<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\HeroBonusPerkType
 *
 * @property int $id
 * @property string $key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\HeroBonus[] $bonuses
 */
class TechPerkType extends AbstractModel
{
    public function bonuses()
    {
        return $this->belongsToMany(
            HeroBonus::class,
            HeroBonusPerk::class,
        )
        ->withPivot('value')
        ->withTimestamps();
    }
}
