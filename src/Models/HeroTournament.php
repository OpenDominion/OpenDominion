<?php

namespace OpenDominion\Models;

use \Illuminate\Database\Eloquent\Builder;

/**
 * OpenDominion\Models\HeroTournament
 *
 * @property int $id
 * @property int $round_id
 * @property string $name
 * @property int $current_round_number
 * @property bool $finished
 * @property int|null $winner_dominion_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Round $round
 * @property-read \OpenDominion\Models\Dominion $winner
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\HeroBattle[] $battles
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\HeroTournamentParticipant[] $participants
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\HeroTournament newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\HeroTournament newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\HeroTournament query()
 * @mixin \Eloquent
 */
class HeroTournament extends AbstractModel
{
    protected $casts = [
        'start_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function winner()
    {
        return $this->belongsTo(Dominion::class, 'winner_dominion_id');
    }

    public function battles()
    {
        return $this->belongsToMany(HeroBattle::class, HeroTournamentBattle::class)
            ->withPivot('round_number');
    }

    public function participants()
    {
        return $this->hasMany(HeroTournamentParticipant::class);
    }

    public function scopeActive(Builder $query)
    {
        return $query->where('finished', false);
    }

    public function hasStarted(): bool
    {
        return $this->start_date !== null && $this->start_date <= now();
    }
}
