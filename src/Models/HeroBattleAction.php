<?php

namespace OpenDominion\Models;

use OpenDominion\Calculators\Dominion\HeroCalculator;

/**
 * OpenDominion\Models\HeroBattleAction
 *
 * @property int $id
 * @property int $hero_battle_id
 * @property int $combatant_id
 * @property int|null $target_combatant_id
 * @property int $turn
 * @property string $action
 * @property int $damage
 * @property int $health
 * @property string $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\HeroBattle $battle
 * @property-read \OpenDominion\Models\HeroCombatant $combatant
 * @property-read \OpenDominion\Models\HeroCombatant|null $target
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\HeroBattle newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\HeroBattle newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\HeroBattle query()
 * @mixin \Eloquent
 */
class HeroBattleAction extends AbstractModel
{
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function battle()
    {
        return $this->belongsTo(HeroBattle::class);
    }

    public function combatant()
    {
        return $this->belongsTo(HeroCombatant::class);
    }

    public function target()
    {
        return $this->belongsTo(HeroCombatant::class, 'target_combatant_id');
    }
}
