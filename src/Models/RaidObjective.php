<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\RaidObjective
 *
 * @property int $id
 * @property int $raid_id
 * @property string $name
 * @property string $description
 * @property int $order
 * @property int $score_required
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Raid $raid
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\RaidObjectiveTactic[] $tactics
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\RaidObjective newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\RaidObjective newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\RaidObjective query()
 * @mixin \Eloquent
 */
class RaidObjective extends AbstractModel
{
    public function raid()
    {
        return $this->belongsTo(Raid::class);
    }

    public function tactics()
    {
        return $this->hasMany(RaidObjectiveTactic::class);
    }
}
