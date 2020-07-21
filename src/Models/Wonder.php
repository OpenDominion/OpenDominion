<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * OpenDominion\Models\Wonder
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property int $power
 * @property bool $active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\WonderPerkType[] $perks
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Wonder active()
 */
class Wonder extends AbstractModel
{
    protected $table = 'wonders';

    protected $casts = [
        'power' => 'integer',
        'active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function perks()
    {
        return $this->belongsToMany(
            WonderPerkType::class,
            'wonder_perks',
            'wonder_id',
            'wonder_perk_type_id'
        )
            ->withTimestamps()
            ->withPivot('value');
    }

    public function getPerkValue(string $key)
    {
        $perks = $this->perks->filter(static function (WonderPerkType $wonderPerkType) use ($key) {
            return ($wonderPerkType->key === $key);
        });

        if ($perks->isEmpty()) {
            return 0; // todo: change to null instead, also add return type and docblock(s)
        }

        return $perks->first()->pivot->value;
    }
}
