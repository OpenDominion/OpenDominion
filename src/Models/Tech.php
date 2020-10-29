<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * OpenDominion\Models\Tech
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property array $prerequisites
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\TechPerkType[] $perks
 */
class Tech extends AbstractModel
{
    protected $table = 'techs';

    protected $casts = [
        'prerequisites' => 'array',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function perks()
    {
        return $this->belongsToMany(
            TechPerkType::class,
            'tech_perks',
            'tech_id',
            'tech_perk_type_id'
        )
            ->withTimestamps()
            ->withPivot('value');
    }

    public function getPerkValue(string $key)
    {
        $perks = $this->perks->filter(static function (TechPerkType $techPerkType) use ($key) {
            return ($techPerkType->key === $key);
        });

        if ($perks->isEmpty()) {
            return 0; // todo: change to null instead, also add return type and docblock(s)
        }

        return $perks->first()->pivot->value;
    }
}
