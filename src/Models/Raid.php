<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\Raid
 *
 * @property int $id
 * @property int $round_id
 * @property string $name
 * @property string $description
 * @property string $reward_resource
 * @property int $reward_amount
 * @property string $completion_reward_resource
 * @property int $completion_reward_amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Round $round
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Raid newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Raid newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Raid query()
 * @mixin \Eloquent
 */
class Raid extends AbstractModel
{
    public function round()
    {
        return $this->belongsTo(Round::class);
    }
}
