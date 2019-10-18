<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\TechPerk
 *
 * @property int $id
 * @property int $tech_id
 * @property int $tech_perk_type_id
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Tech $tech
 * @property-read \OpenDominion\Models\TechPerkType $type
 */
class TechPerk extends AbstractModel
{
    public function tech()
    {
        return $this->belongsTo(Tech::class);
    }

    public function type()
    {
        return $this->belongsTo(TechPerkType::class, 'tech_perk_type_id');
    }
}
