<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\RaidContribution
 *
 * @property int $id
 * @property int $realm_id
 * @property int $dominion_id
 * @property int $raid_objective_id
 * @property string $type
 * @property int $score - TODO: or float?
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Dominion $dominion
 * @property-read \OpenDominion\Models\RaidObjective $objective
 * @property-read \OpenDominion\Models\Realm $realm
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\RaidContribution newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\RaidContribution newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\RaidContribution query()
 * @mixin \Eloquent
 */
class RaidContribution extends AbstractModel
{
    protected $fillable = [
        'realm_id',
        'dominion_id',
        'raid_objective_id',
        'type',
        'score',
        'created_at'
    ];

    public function dominion()
    {
        return $this->belongsTo(Dominion::class);
    }

    public function objective()
    {
        return $this->belongsTo(RaidObjective::class, 'raid_objective_id');
    }

    public function realm()
    {
        return $this->belongsTo(Realm::class);
    }
}
