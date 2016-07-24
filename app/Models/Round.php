<?php

namespace OpenDominion\Models;

use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property int $round_league_id
 * @property int $number
 * @property string $name
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read RoundLeague $league
 * @property-read Realm[] $realms
 * @property-read boolean $started
 * @property-read int $days_until_start
 * @property-read int $duration_in_days
 */
class Round extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $dates = ['start_date', 'end_date', 'created_at', 'updated_at'];

    public function league()
    {
        return $this->hasOne(RoundLeague::class, 'id', 'round_league_id');
    }

    public function realms()
    {
        return $this->hasMany(Realm::class);
    }

    /**
     * Return whether a round has started or not.
     *
     * @return bool
     */
    public function getStartedAttribute()
    {
        return ($this->start_date <= new DateTime('today'));
    }

    /**
     * Returns the amount in days until the round starts, from today on.
     *
     * @return int
     */
    public function getDaysUntilStartAttribute()
    {
        return $this->start_date->diffInDays(Carbon::now());
    }

    /**
     * Returns the round duration in days.
     *
     * @return int
     */
    public function getDurationInDaysAttribute()
    {
        return $this->start_date->diffInDays($this->end_date);
    }
}
