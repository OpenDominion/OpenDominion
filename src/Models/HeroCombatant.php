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
 * @property int $level
 * @property int $health
 * @property int $attack
 * @property int $defense
 * @property int $evasion
 * @property int $focus
 * @property int $counter
 * @property int $recover
 * @property int $current_health
 * @property bool $has_focus
 * @property array|null $actions
 * @property string|null $last_action
 * @property \Illuminate\Support\Carbon|null $last_action_at
 * @property int $time_bank
 * @property bool|null $automated
 * @property string|null $strategy
 * @property array|null $abilities
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
    protected $casts = [
        'actions' => 'array',
        'abilities' => 'array',
    ];

    protected $dates = ['last_action_at', 'created_at', 'updated_at'];

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
            ($this->actions != null && count($this->actions) > 0);
    }

    public function timeElapsed()
    {
        if ($this->isReady()) {
            return 0;
        }

        $lastProcessedAt = $this->battle->last_processed_at ?? $this->created_at;
        return $lastProcessedAt->diffInSeconds(now());
    }

    public function timeLeft()
    {
        return max(0, $this->time_bank - $this->timeElapsed());
    }
}
