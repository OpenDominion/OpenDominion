<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * OpenDominion\Models\Spell
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string $category
 * @property float $cost_mana
 * @property float $cost_strength
 * @property int $duration
 * @property int $cooldown
 * @property array $races
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\SpellPerkType[] $perks
 */
class Spell extends AbstractModel
{
    protected $table = 'spells';

    protected $casts = [
        'races' => 'array',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function perks()
    {
        return $this->belongsToMany(
            SpellPerkType::class,
            SpellPerk::class
        )
        ->withPivot('value')
        ->withTimestamps();
    }

    public function hasPerk(string $key)
    {
        return $this->perks->keyBy('key')->has($key);
    }

    public function getPerkValue(string $key)
    {
        $perks = $this->perks->filter(static function (SpellPerkType $spellPerkType) use ($key) {
            return ($spellPerkType->key === $key);
        });

        if ($perks->isEmpty()) {
            return 0; // todo: change to null instead, also add return type and docblock(s)
        }

        return $perks->first()->pivot->value;
    }

    public function isHarmful() {
        return $this->category == 'hostile' || $this->category == 'war' || $this->key == 'burning';
    }
}
