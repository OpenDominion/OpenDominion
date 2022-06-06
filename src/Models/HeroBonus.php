<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * OpenDominion\Models\HeroBonus
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property int $level
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\HeroBonusPerkType[] $bonuses
 */
class HeroBonus extends AbstractModel
{
    protected $table = 'hero_bonuses';

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function perks()
    {
        return $this->belongsToMany(
            HeroBonusPerkType::class,
            HeroBonusPerk::class
        )
        ->withPivot('value')
        ->withTimestamps();
    }

    public function getPerkValue(string $key)
    {
        $perks = $this->perks->filter(static function (HeroBonusPerkType $heroBonusPerkType) use ($key) {
            return ($heroBonusPerkType->key === $key);
        });

        if ($perks->isEmpty()) {
            return 0; // todo: change to null instead, also add return type and docblock(s)
        }

        return $perks->first()->pivot->value;
    }
}
