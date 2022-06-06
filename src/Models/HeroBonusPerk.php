<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\HeroBonusPerk
 *
 * @property int $id
 * @property int $hero_bonus_id
 * @property int $hero_bonus_perk_type_id
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\HeroBonus $bonus
 * @property-read \OpenDominion\Models\HeroBonusPerkType $type
 */
class HeroBonusPerk extends AbstractModel
{
    public function bonus()
    {
        return $this->belongsTo(HeroBonus::class);
    }

    public function type()
    {
        return $this->belongsTo(HeroBonusPerkType::class, 'hero_bonus_perk_type_id');
    }
}
