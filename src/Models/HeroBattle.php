<?php

namespace OpenDominion\Models;

use \Illuminate\Support\Carbon;
use \Illuminate\Database\Eloquent\Builder;
use OpenDominion\Calculators\Dominion\HeroCalculator;

/**
 * OpenDominion\Models\HeroBattle
 *
 * @property int $id
 * @property int $round_id
 * @property int $current_turn
 * @property int|null $winner_combatant_id
 * @property bool $finished
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Round $round
 * @property-read \OpenDominion\Models\HeroCombatant $winner
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\HeroBattleAction[] $actions
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\HeroCombatant[] $combatants
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\HeroBattle newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\HeroBattle newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\HeroBattle query()
 * @mixin \Eloquent
 */
class HeroBattle extends AbstractModel
{
    protected $dates = ['created_at', 'updated_at'];

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function winner()
    {
        return $this->belongsTo(HeroCombatant::class, 'winner_combatant_id');
    }

    public function actions()
    {
        return $this->hasMany(HeroBattleAction::class);
    }

    public function combatants()
    {
        return $this->hasMany(HeroCombatant::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('finished', false);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('finished', true);
    }

    public function startTime(): Carbon
    {
        return $this->created_at->addHours(12)->startOfHour();
    }

    public function nextTurnTime(): Carbon
    {
        if (!$this->hasStarted()) {
            return $this->startTime();
        }
        return $this->updated_at->addHours(1)->startOfHour();
    }

    public function ticksUntilNextTurn(): int
    {
        $thisTick = now()->startOfHour();
        if ($thisTick > $this->nextTurnTime()) {
            return -1;
        }
        return $this->nextTurnTime()->diffInHours($thisTick);
    }

    public function hasStarted(): bool
    {
        return $this->startTime() < now();
    }

    public function turnProcessed(): bool
    {
        return $this->nextTurnTime() > now();
    }
}
