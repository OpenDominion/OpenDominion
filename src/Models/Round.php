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
 * @property bool $assignment_complete
 * @property int $tech_version
 * @property int $valor
 * @property int|null $discord_guild_id
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
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\HeroTournament[] $tournaments
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

    public function activeDominions()
    {
        return $this->dominions()->where('locked_at', null);
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

    public function raids()
    {
        return $this->hasMany(Raid::class);
    }

    public function realms()
    {
        return $this->hasMany(Realm::class);
    }

    public function tournaments()
    {
        return $this->hasMany(HeroTournament::class);
    }

    public function wars()
    {
        return $this->hasManyThrough(
            RealmWar::class,
            Realm::class,
            'round_id',
            'source_realm_id',
            'id',
            'id'
        );
    }

    public function wonders()
    {
        return $this->hasMany(RoundWonder::class);
    }

    public function forumThreads()
    {
        return $this->hasMany(Forum\Thread::class);
    }

    // Query Scopes

    /**
     * Scope a query to include only active rounds.
     * Used by TickService to process ticks and reset daily bonuses.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('start_date', '<=', now()->subHours(1))
            ->where('end_date', '>', now());
    }

    /**
     * Scope a query to include only active rounds including a final update at round end.
     * Used by TickService to process daily rankings.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActiveRankings(Builder $query): Builder
    {
        return $query
            ->where('start_date', '<=', now()->subHours(1))
            ->where('end_date', '>', now()->subHours(1));
    }

    /**
     * Scope a query to include only rounds that are active the following hour.
     * Used by TickService to generate Non-Player Dominions.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActiveSoon(Builder $query): Builder
    {
        return $query
            ->where('start_date', '<', now()->addHours(1))
            ->where('start_date', '>=', now());
    }

    /**
     * Scope a query to include only rounds that are ready to have realms assigned.
     * Used by TickService to trigger realm assignment.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeReadyForAssignment(Builder $query): Builder
    {
        $assignmentHours = \OpenDominion\Services\RealmFinderService::ASSIGNMENT_HOURS_BEFORE_START;

        return $query
            ->where('start_date', '<', now()->addHours($assignmentHours))
            ->where('start_date', '>=', now()->addHours($assignmentHours - 1));
    }

    /**
     * Returns the graveyard realm for the round.
     *
     * @return Realm|null
     */
    public function graveyard()
    {
        return $this->realms()->where('number', 0)->first();
    }

    /**
     * Returns the scheduled realm assignment date.
     *
     * @return bool
     */
    public function realmAssignmentDate()
    {
        $assignmentHours = \OpenDominion\Services\RealmFinderService::ASSIGNMENT_HOURS_BEFORE_START;

        return $this->start_date->copy()->subHours($assignmentHours);
    }

    /**
     * Returns whether a user can register to this round.
     *
     * @return bool
     */
    public function packRegistrationOpen()
    {
        if (now() > $this->realmAssignmentDate() && now() < $this->start_date) {
            // Cannot register packs between realm assignment and OOP
            return false;
        }
        return true;
    }

    /**
     * Returns a string representation of time until realm assignment.
     *
     * @return string
     */
    public function timeUntilRealmAssignment()
    {
        return now()->longAbsoluteDiffForHumans($this->realmAssignmentDate(), 2);
    }

    /**
     * Returns whether the user already has a dominion registered in this round.
     *
     * @return bool
     */
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
     * Returns whether a round has ended.
     *
     * @return bool
     */
    public function hasAssignedRealms()
    {
        return ($this->realmAssignmentDate() <= now()) && $this->assignment_complete;
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

    public function offensiveActionsAreEnabledButCanBeDisabled(): bool
    {
        if ($this->hasOffensiveActionsDisabled()) {
            return false;
        }

        return now()->diffInHours($this->end_date) < 15;
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
     * Returns a string representation of time until round start.
     *
     * @return string
     */
    public function timeUntilStart()
    {
        return now()->longAbsoluteDiffForHumans($this->start_date, 2);
    }

    /**
     * Returns the amount in days until the round ends.
     *
     * @return int
     */
    public function daysUntilEnd()
    {
        return now()->diffInDays($this->end_date);
    }

    /**
     * Returns the number of hours until daily bonus/rankings.
     *
     * @return int
     */
    public function hoursUntilReset()
    {
        $hoursUntilReset = $this->end_date->hour - now()->hour;
        if ($hoursUntilReset < 1) {
            $hoursUntilReset += 24;
        }
        return $hoursUntilReset;
    }

    /**
     * Returns the amount in hours since the current round day started.
     *
     * @return int
     */
    public function hoursInDay(Carbon $datetime = null)
    {
        if ($datetime == null) {
            $datetime = now();
        }
        if ($datetime < $this->start_date) {
            return 1;
        }
        $hoursInDay = $datetime->hour - $this->start_date->hour + 1;
        if ($hoursInDay < 1) {
            $hoursInDay += 24;
        }
        return $hoursInDay;
    }

    /**
     * Returns the amount in days since the round started.
     *
     * @return int
     */
    public function daysInRound(Carbon $datetime = null)
    {
        if ($datetime == null) {
            $datetime = now();
        }
        if ($datetime < $this->start_date) {
            return 1;
        }
        return $this->start_date->copy()->subDays(1)->diffInDays($datetime);
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

    /**
     * Returns the tick number for current time.
     *
     * @return int
     */
    public function getTick()
    {
        if (!$this->hasStarted()) {
            return 0;
        }
        return (24 * ($this->daysInRound() - 1)) + ($this->hoursInDay() - 1);
    }
}
