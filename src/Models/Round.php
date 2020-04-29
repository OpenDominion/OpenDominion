<?php

namespace OpenDominion\Models;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Builder;

/**
 * OpenDominion\Models\Round
 *
 * @property int $id
 * @property int $round_league_id
 * @property int $number
 * @property string $name
 * @property int $realm_size
 * @property int $pack_size
 * @property int $players_per_race
 * @property bool $mixed_alignment
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property \Illuminate\Support\Carbon $offensive_actions_prohibited_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Dominion[] $dominions
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\GameEvent[] $gameEvents
 * @property-read \OpenDominion\Models\RoundLeague $league
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Pack[] $packs
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Realm[] $realms
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Forum\Thread[] $forumThreads
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Round active()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Round newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Round newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Round query()
 * @mixin \Eloquent
 */
class Round extends AbstractModel
{
    protected $dates = [
        'start_date',
        'end_date',
        'offensive_actions_prohibited_at',
        'created_at',
        'updated_at'
    ];

    // Eloquent Relations

    public function dominions()
    {
        return $this->hasManyThrough(Dominion::class, Realm::class);
    }

    public function gameEvents()
    {
        return $this->hasMany(GameEvent::class);
    }

    public function league()
    {
        return $this->hasOne(RoundLeague::class, 'id', 'round_league_id');
    }

    public function packs()
    {
        return $this->hasMany(Pack::class);
    }

    public function realms()
    {
        return $this->hasMany(Realm::class);
    }

    public function forumThreads()
    {
        return $this->hasMany(Forum\Thread::class);
    }

    // Query Scopes

    /**
     * Scope a query to include only active rounds (after protection).
     * Used by TickService to process ticks.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        $protectionHours = \OpenDominion\Services\Dominion\ProtectionService::PROTECTION_DURATION_IN_HOURS;

        return $query
            ->where('start_date', '<=', now()->subHours($protectionHours + 1))
            ->where('end_date', '>', now());
    }

    /**
     * Scope a query to include only active rounds (after protection) including a final update at round end.
     * Used by TickService to process daily rankings.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeLive(Builder $query): Builder
    {
        $protectionHours = \OpenDominion\Services\Dominion\ProtectionService::PROTECTION_DURATION_IN_HOURS;

        return $query
            ->where('start_date', '<=', now()->subHours($protectionHours + 1))
            ->where('end_date', '>', now()->addHours(1));
    }

    /**
     * Returns whether a user can register to this round.
     *
     * @return bool
     */
    public function openForRegistration()
    {
        return ($this->start_date <= new Carbon('+3 days midnight'));
    }

    /**
     * Returns the amount in days until registration opens.
     *
     * @return int
     */
    public function daysUntilRegistration()
    {
        return $this->start_date->diffInDays(new Carbon('+3 days midnight'));
    }

    public function userAlreadyRegistered(User $user)
    {
        $results = DB::table('dominions')
            ->where('user_id', $user->id)
            ->where('round_id', $this->id)
            ->limit(1)
            ->get();

        return (\count($results) === 1);
    }

    /**
     * Returns whether a round has started.
     *
     * @return bool
     */
    public function hasStarted()
    {
        return ($this->start_date <= now());
    }

    /**
     * Returns whether a round has ended.
     *
     * @return bool
     */
    public function hasEnded()
    {
        return ($this->end_date <= now());
    }

    /**
     * Returns whether offensive actions (and exploration) are disabled for the
     * rest of the round.
     *
     * Actions like these are disabled near the end of the round to prevent
     * suicides and whatnot.
     *
     * @return bool
     */
    public function hasOffensiveActionsDisabled(): bool
    {
        if ($this->offensive_actions_prohibited_at === null) {
            return false;
        }

        return ($this->offensive_actions_prohibited_at <= now());
    }

    /**
     * Returns whether a round is active.
     *
     * @return bool
     */
    public function isActive()
    {
        return ($this->hasStarted() && !$this->hasEnded());
    }

    /**
     * Returns the amount in days until the round starts.
     *
     * @return int
     */
    public function daysUntilStart()
    {
        return $this->start_date->diffInDays(today());
    }

    /**
     * Returns the amount in days until the round ends.
     *
     * @return int
     */
    public function daysUntilEnd()
    {
        return $this->end_date->diffInDays(today());
    }

    /**
     * Returns the round duration in days.
     *
     * @return int
     */
    public function durationInDays()
    {
        return $this->start_date->diffInDays($this->end_date);
    }
}
