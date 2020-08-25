<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\WonderPerk
 *
 * @property int $id
 * @property int $wonder_id
 * @property int $wonder_perk_type_id
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Wonder $wonder
 * @property-read \OpenDominion\Models\WonderPerkType $type
 */
class WonderPerk extends AbstractModel
{
    public function wonder()
    {
        return $this->belongsTo(Wonder::class);
    }

    public function type()
    {
        return $this->belongsTo(WonderPerkType::class, 'wonder_perk_type_id');
    }
}
