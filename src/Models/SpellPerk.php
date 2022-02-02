<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\SpellPerk
 *
 * @property int $id
 * @property int $spell_id
 * @property int $spell_perk_type_id
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Spell $spell
 * @property-read \OpenDominion\Models\SpellPerkType $type
 */
class SpellPerk extends AbstractModel
{
    public function spell()
    {
        return $this->belongsTo(Spell::class);
    }

    public function type()
    {
        return $this->belongsTo(SpellPerkType::class, 'spell_perk_type_id');
    }
}
