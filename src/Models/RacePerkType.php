<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\RacePerkType
 *
 * @property int $id
 * @property string $key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Race[] $races
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\RacePerkType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\RacePerkType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\RacePerkType query()
 * @mixin \Eloquent
 */
class RacePerkType extends AbstractModel
{
    public function races()
    {
        return $this->belongsToMany(
            Race::class,
            'race_perks',
            'race_perk_type_id',
            'race_id'
        )
            ->withTimestamps();
    }
}
