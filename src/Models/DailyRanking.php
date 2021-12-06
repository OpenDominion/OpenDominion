<?php

namespace OpenDominion\Models;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Builder;

/**
 * OpenDominion\Models\DailyRanking
 *
 * @property int $id
 * @property int $round_id
 * @property int $dominion_id
 * @property string $dominion_name
 * @property string $race_name
 * @property int $realm_number
 * @property string $realm_name
 * @property string $key
 * @property int $value
 * @property int $rank
 * @property int $previous_rank
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Dominion $dominion
 * @property-read \OpenDominion\Models\Round $round
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Round active()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Round newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Round newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Round query()
 * @mixin \Eloquent
 */
class DailyRanking extends AbstractModel
{
    protected $dates = [
        'created_at',
        'updated_at'
    ];

    // Eloquent Relations

    public function dominion()
    {
        return $this->hasOne(Dominion::class, 'id', 'dominion_id');
    }

    public function round()
    {
        return $this->hasOne(Round::class, 'id', 'round_id');
    }
}
