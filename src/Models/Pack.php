<?php

namespace OpenDominion\Models;

use Carbon\Carbon;

/**
 * OpenDominion\Models\Pack
 *
 * @property int $id
 * @property int $round_id
 * @property int|null $realm_id
 * @property int $creator_dominion_id
 * @property string $name
 * @property string $password
 * @property int $size
 * @property \Illuminate\Support\Carbon|null $closed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Dominion[] $dominions
 * @property-read \OpenDominion\Models\Realm|null $realm
 * @property-read \OpenDominion\Models\Round $round
 * @property-read \OpenDominion\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Pack newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Pack newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Pack query()
 * @mixin \Eloquent
 */
class Pack extends AbstractModel
{
    protected $dates = ['closed_at', 'created_at', 'updated_at'];

    //    public function creatorDominion()
//    {
//        return $this->hasOne(Dominion::class); // todo
//    }

    public function dominions()
    {
        return $this->hasMany(Dominion::class);
    }

    public function realm()
    {
        return $this->belongsTo(Realm::class);
    }

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isFull(): bool
    {
        return ($this->dominions->count() === $this->size);
    }

    public function isClosed(): bool
    {
        return (($this->closed_at !== null) || $this->isFull() || ($this->getClosingDate() < now()));
    }

    public function getClosingDate(): Carbon
    {
        return max($this->created_at, $this->round->start_date)->addDays(3);
    }
}
