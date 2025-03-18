<?php

namespace OpenDominion\Models;

use \Illuminate\Database\Eloquent\Builder;

/**
 * OpenDominion\Models\HeroTournamentParticipant
 *
 * @property int $id
 * @property int $hero_tournament_id
 * @property int $hero_id
 * @property int $wins
 * @property int $losses
 * @property int $draws
 * @property int|null $standing
 * @property bool $eliminated
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\HeroTournament $tournament
 * @property-read \OpenDominion\Models\Hero $hero
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\HeroTournament newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\HeroTournament newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\HeroTournament query()
 * @mixin \Eloquent
 */
class HeroTournamentParticipant extends AbstractModel
{
    protected $dates = ['created_at', 'updated_at'];

    public function tournament()
    {
        return $this->belongsTo(HeroTournament::class);
    }

    public function hero()
    {
        return $this->belongsTo(Hero::class);
    }
}
