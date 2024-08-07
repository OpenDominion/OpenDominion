<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\HeroUpgradePerk
 *
 * @property int $id
 * @property int $hero_upgrade_id
 * @property int $key
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\HeroUpgrade $upgrade
 */
class HeroUpgradePerk extends AbstractModel
{
    public function upgrade()
    {
        return $this->belongsTo(HeroUpgrade::class, 'hero_upgrade_id');
    }
}
