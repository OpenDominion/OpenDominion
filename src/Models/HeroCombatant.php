<?php

namespace OpenDominion\Models;

use OpenDominion\Calculators\Dominion\HeroCalculator;

/**
 * OpenDominion\Models\HeroCombatant
 * 
 * @property int $id
 * @property int $hero_battle_id
 * @property int $hero_id
 * @property int $dominion_id
 * @property string $name
 * @property int $health
 * @property int $attack
 * @property int $defense
 * @property int $evasion
 * @property int $focus
 * @property int $counter
 * @property int $recover
 * @property int $current_health
 * @property bool $has_focus
 * @property string|null $current_action
 * @property string|null $last_action
 * @property array|null $actions
 * @property bool|null $automated
 * @property string|null $strategy
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\HeroBattle $battle
 * @property-read \OpenDominion\Models\Hero $hero
 * @property-read \OpenDominion\Models\Dominion $dominion
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\HeroBattle newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\HeroBattle newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\HeroBattle query()
 * @mixin \Eloquent
 */
class HeroCombatant extends AbstractModel
{
    protected $casts = ['actions' => 'array'];

    protected $dates = ['created_at', 'updated_at'];

    public function battle()
    {
        return $this->belongsTo(HeroBattle::class, 'hero_battle_id');
    }

    public function hero()
    {
        return $this->belongsTo(Hero::class);
    }

    public function dominion()
    {
        return $this->belongsTo(Dominion::class);
    }

    public function isReady()
    {
        return ($this->automated == true) ||
            ($this->automated == null && $this->battle->created_at > now()->subHours(12)->startOfHour()) ||
            ($this->actions != null && count($this->actions) > 0);
    }
}
