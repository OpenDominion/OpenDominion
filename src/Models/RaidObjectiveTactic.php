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
    protected $casts = [
        'attributes' => 'array',
        'bonuses' => 'array',
    ];

    public function objective()
    {
        return $this->belongsTo(RaidObjective::class);
    }
}
