<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\UnitPerkType
 *
 * @property int $id
 * @property string $key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Unit[] $units
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\UnitPerkType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\UnitPerkType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\UnitPerkType query()
 * @mixin \Eloquent
 */
class UnitPerkType extends AbstractModel
{
    public function units()
    {
        return $this->belongsToMany(
            Unit::class,
            'unit_perks',
            'unit_perk_type_id',
            'unit_id'
        )
            ->withTimestamps();
    }
}
