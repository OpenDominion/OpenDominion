<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * OpenDominion\Models\HeroUpgrade
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property int $level
 * @property string $type
 * @property string $icon
 * @property array $classes
 * @property bool $active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\HeroUpgradePerks[] $perks
 */
class HeroUpgrade extends AbstractModel
{
    protected $table = 'hero_upgrades';

    protected $casts = [
        'classes' => 'array',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function perks()
    {
        return $this->hasMany(HeroUpgradePerk::class);
    }

    public function getPerkValue(string $key)
    {
        $perk = $this->perks->where('key', $key)->first();

        if ($perk === null) {
            return 0;
        }

        return $perk->value;
    }
}
