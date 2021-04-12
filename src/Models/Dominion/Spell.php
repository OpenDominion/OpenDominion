<?php

namespace OpenDominion\Models\Dominion;

use OpenDominion\Models\AbstractModel;
use OpenDominion\Models\Dominion;

/**
 * OpenDominion\Models\Dominion\Spell
 *
 * @property int $dominion_id
 * @property string $spell
 * @property int $duration
 * @property int $cast_by_dominion_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Dominion\Spell newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Dominion\Spell newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Dominion\Spell query()
 * @mixin \Eloquent
 */
class Spell extends AbstractModel
{
    protected $table = 'active_spells';

    protected $guarded = ['created_at', 'updated_at'];

    protected $dates = ['created_at', 'updated_at'];

    public function castByDominion()
    {
        return $this->belongsTo(Dominion::class, 'cast_by_dominion_id');
    }
}
