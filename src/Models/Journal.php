<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\Journal
 *
 * @property int $id
 * @property int $dominion_id
 * @property string $content
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Dominion $dominion
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Journal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Journal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Journal query()
 * @mixin \Eloquent
 */
class Journal extends AbstractModel
{
    protected $table = 'dominion_journals';

    protected $dates = ['created_at', 'updated_at'];

    public function dominion()
    {
        return $this->belongsTo(Dominion::class);
    }

    /**
     * Returns the hour of the day when the journal entry was created.
     *
     * @return int
     */
    public function hoursInDay()
    {
        return $this->dominion->round->hoursInDay($this->created_at);
    }

    /**
     * Returns the day of the round when the journal entry was created.
     *
     * @return int
     */
    public function daysInRound()
    {
        return $this->dominion->round->daysInRound($this->created_at);
    }
}
