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
 * @property int $rating
 * @property \Illuminate\Support\Carbon|null $closed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Dominion[] $dominions
 * @property-read \OpenDominion\Models\Dominion $creatorDominion
 * @property-read \OpenDominion\Models\Realm|null $realm
 * @property-read \OpenDominion\Models\Round $round
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Pack newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Pack newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Pack query()
 * @mixin \Eloquent
 */
class Pack extends AbstractModel
{
    protected $dates = ['closed_at', 'created_at', 'updated_at'];

    public function creatorDominion()
    {
        return $this->belongsTo(Dominion::class, 'creator_dominion_id');
    }

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

    public function users()
    {
        return $this->hasManyThrough(User::class, Dominion::class, 'pack_id', 'id', 'id', 'user_id');
    }

    public function sizeAllocated(): int
    {
        if ($this->isClosed()) {
            return $this->dominions->count();
        }
        return $this->size;
    }

    public function remainingSlots(): int
    {
        if ($this->isClosed()) {
            return 0;
        }
        return $this->size - $this->dominions->count();
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
        if ($this->round->realmAssignmentDate() > now()) {
            return $this->round->realmAssignmentDate();
        }
        return $this->created_at->copy()->addDays(3);
    }

    public function close()
    {
        $this->size = $this->dominions->count();
        if ($this->closed_at == null) {
            $this->closed_at = now();
        }
        $userRatings = $this->users->pluck('rating')->toArray();
        $this->rating = root_mean_square($userRatings);
        $this->save();
    }
}
