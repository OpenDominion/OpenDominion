<?php

namespace OpenDominion\Models;

use Carbon\Carbon;

class Round extends AbstractModel
{
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
     * Returns whether a user can register to this round.
     *
     * @return bool
     */
    public function canRegister()
    {
        return ($this->start_date <= new Carbon('+3 days'));
    }

    /**
     * Returns the amount in days until registration opens.
     *
     * @return int
     */
    public function daysUntilRegistration()
    {
        return $this->start_date->diffInDays(new Carbon('+3 days'));
    }

    /**
     * Return whether a round has started or not.
     *
     * @return bool
     */
    public function hasStarted()
    {
        return ($this->start_date <= Carbon::today());
    }

    /**
     * Returns the amount in days until the round starts, from today on.
     *
     * @return int
     */
    public function daysUntilStart()
    {
        return $this->start_date->diffInDays(Carbon::now());
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
