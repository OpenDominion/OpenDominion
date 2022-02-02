<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\TechPerkType
 *
 * @property int $id
 * @property string $key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Tech[] $techs
 */
class TechPerkType extends AbstractModel
{
    public function techs()
    {
        return $this->belongsToMany(
            Tech::class,
            TechPerk::class,
        )
        ->withPivot('value')
        ->withTimestamps();
    }
}
