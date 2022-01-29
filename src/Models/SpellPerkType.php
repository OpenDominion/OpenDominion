<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\SpellPerkType
 *
 * @property int $id
 * @property string $key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Spell[] $spells
 */
class SpellPerkType extends AbstractModel
{
    public function spells()
    {
        return $this->belongsToMany(
            Spell::class,
            SpellPerk::class,
        )
        ->withPivot('value')
        ->withTimestamps();
    }
}
