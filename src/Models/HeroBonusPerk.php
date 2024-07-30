<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\HeroBonusPerk
 *
 * @property int $id
 * @property int $hero_bonus_id
 * @property int $key
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\HeroBonus $bonus
 */
class HeroBonusPerk extends AbstractModel
{
    public function bonus()
    {
        return $this->belongsTo(HeroBonus::class, 'hero_bonus_id');
    }
}
