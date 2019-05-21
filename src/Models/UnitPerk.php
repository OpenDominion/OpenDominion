<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\UnitPerk
 *
 * @property int $id
 * @property int $unit_id
 * @property int $unit_perk_type_id
 * @property float|null $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\UnitPerkType $type
 * @property-read \OpenDominion\Models\Unit $unit
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\UnitPerk newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\UnitPerk newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\UnitPerk query()
 * @mixin \Eloquent
 */
class UnitPerk extends AbstractModel
{
    protected $casts = [
        'value' => 'float',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function type()
    {
        return $this->belongsTo(UnitPerkType::class, 'unit_perk_type_id');
    }
}
