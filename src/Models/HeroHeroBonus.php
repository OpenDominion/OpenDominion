<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\HeroHeroBonus
 *
 * @property int $hero_id
 * @property int $hero_bonus_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Hero $hero
 * @property-read \OpenDominion\Models\HeroBonus $bonus
 */
class HeroHeroBonus extends AbstractPivot
{
    protected $table = 'hero_hero_bonuses';

    public function hero()
    {
        return $this->belongsTo(Hero::class, 'hero_id');
    }

    public function bonus()
    {
        return $this->belongsTo(HeroBonus::class, 'hero_bonus_id');
    }
}
