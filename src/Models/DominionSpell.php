<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\DominionSpell
 *
 * @property int $dominion_id
 * @property int $spell_id
 * @property int $duration
 * @property int $cast_by_dominion_id
 * @property array $applications
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Dominion $dominion
 * @property-read \OpenDominion\Models\Spell $spell
 */
class DominionSpell extends AbstractPivot
{
    protected $table = 'dominion_spells';

    protected $casts = ['applications' => 'array'];

    public function dominion()
    {
        return $this->belongsTo(Dominion::class, 'dominion_id');
    }

    public function castByDominion()
    {
        return $this->belongsTo(Dominion::class, 'cast_by_dominion_id');
    }

    public function spell()
    {
        return $this->belongsTo(Spell::class, 'spell_id');
    }

    public function getApplicationsAttribute($value)
    {
        if ($value == null) {
            return [
                'dominion_ids' => [],
                'realm_ids' => [],
            ];
        }

        return json_decode($value);
    }
}
