<?php

namespace OpenDominion\Models;

use \Illuminate\Database\Eloquent\Builder;

/**
 * OpenDominion\Models\HeroBattle
 *
 * @property int $id
 * @property int $round_id
 * @property int $current_turn
 * @property bool $pvp
 * @property string $mode
 * @property string|null $encounter_key
 * @property int $seed
 * @property int|null $raid_tactic_id
 * @property int|null $winner_combatant_id representative of the winning team (first human, else first combatant)
 * @property int|null $winning_team
 * @property array|null $effects team and field effect instances: {team: {n: [...]}, field: [...]}
 * @property string|null $initial_state JSON snapshot of the battle as created (see StateSnapshot), for replays
 * @property bool $finished
 * @property \Illuminate\Support\Carbon|null $last_processed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Round $round
 * @property-read \OpenDominion\Models\HeroCombatant $winner
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\HeroBattleAction[] $actions
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\HeroCombatant[] $combatants
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\HeroTournament[] $tournaments
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\HeroBattle newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\HeroBattle newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\HeroBattle query()
 * @mixin \Eloquent
 */
class HeroBattle extends AbstractModel
{
    protected $casts = [
        'pvp' => 'boolean',
        'finished' => 'boolean',
        'effects' => 'array',
        'last_processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function tournaments()
    {
        return $this->belongsToMany(HeroTournament::class, HeroTournamentBattle::class);
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

    public function tactic()
    {
        return $this->belongsTo(RaidObjectiveTactic::class, 'raid_tactic_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('finished', false);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('finished', true);
    }

    /**
     * @return \Illuminate\Support\Collection<int, HeroCombatant>
     */
    public function winningCombatants(): \Illuminate\Support\Collection
    {
        if (!$this->finished || $this->winning_team === null) {
            return collect();
        }

        return $this->combatants->where('team', $this->winning_team)->values();
    }

    public function isDraw(): bool
    {
        return $this->finished && $this->winning_team === null;
    }

    public function isWinner(?HeroCombatant $combatant): bool
    {
        return $combatant !== null && $this->finished && $this->winning_team !== null && $combatant->team === $this->winning_team;
    }

    public function hasWinningDominion(int $dominionId): bool
    {
        return $this->winningCombatants()->contains('dominion_id', $dominionId);
    }

    /**
     * Display name for the winning side, e.g. "Alice & Bob".
     */
    public function winnerLabel(): ?string
    {
        $winners = $this->winningCombatants();
        if ($winners->isEmpty()) {
            return null;
        }

        $humans = $winners->whereNotNull('hero_id');

        return ($humans->isNotEmpty() ? $humans : $winners->take(1))->pluck('name')->implode(' & ');
    }

    /**
     * e.g. "Alice & Bob vs Admiral Varos"
     */
    public function matchupLabel(): string
    {
        return $this->combatants
            ->groupBy('team')
            ->sortKeys()
            ->map(fn ($members) => $members->pluck('name')->implode(' & '))
            ->implode(' vs ');
    }

    public function allReady(): bool
    {
        return $this->combatants()->get()->filter(function ($combatant) {
            return !$combatant->isReady();
        })->count() == 0;
    }
}
