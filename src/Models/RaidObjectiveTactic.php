<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\RaidObjectiveTactic
 *
 * @property int $id
 * @property int $raid_objective_id
 * @property string $type
 * @property string $name - TODO: Should name go inside of attributes?
 * @property float $modifier
 * @property array $attributes - TODO: i.e. strength cost, mana cost, casualties taken, etc.
 * @property array $bonuses - TODO: i.e. specific race, tech, hero class, etc.
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\RaidObjective $objective
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\RaidObjectiveTactic newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\RaidObjectiveTactic newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\RaidObjectiveTactic query()
 * @mixin \Eloquent
 */
class RaidObjectiveTactic extends AbstractModel
{
    protected $fillable = [
        'raid_objective_id',
        'type',
        'name',
        'attributes',
        'bonuses',
    ];

    protected $casts = [
        'attributes' => 'array',
        'bonuses' => 'array',
    ];

    public function objective()
    {
        return $this->belongsTo(RaidObjective::class, 'raid_objective_id');
    }

    public function getSortOrderAttribute(): int
    {
        $types = [
            'hero',
            'investment',
            'exploration',
            'espionage',
            'magic',
            'invasion',
        ];

        $index = array_search($this->type, $types);
        return $index !== false ? $index : 999;
    }
}
