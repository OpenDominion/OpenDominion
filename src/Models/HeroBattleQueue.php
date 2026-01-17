<?php

namespace OpenDominion\Models;

use \Illuminate\Database\Eloquent\Builder;

/**
 * OpenDominion\Models\HeroBattleQueue
 *
 * @property int $id
 * @property int $hero_id
 * @property int $experience
 * @property int $rating
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Hero $hero
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\HeroBattle newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\HeroBattle newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\HeroBattle query()
 * @mixin \Eloquent
 */
class HeroBattleQueue extends AbstractModel
{
    protected $table = 'hero_battle_queue';

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function hero()
    {
        return $this->belongsTo(Hero::class);
    }
}
