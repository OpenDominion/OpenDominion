<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\HeroHeroUpgrade
 *
 * @property int $hero_id
 * @property int $hero_upgrade_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Hero $hero
 * @property-read \OpenDominion\Models\HeroUpgrade $upgrade
 */
class HeroHeroUpgrade extends AbstractPivot
{
    protected $table = 'hero_hero_upgrades';

    public function hero()
    {
        return $this->belongsTo(Hero::class, 'hero_id');
    }

    public function upgrade()
    {
        return $this->belongsTo(HeroUpgrade::class, 'hero_upgrade_id');
    }
}
