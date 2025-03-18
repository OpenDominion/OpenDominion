<?php

namespace OpenDominion\Models;

use \Illuminate\Database\Eloquent\Builder;

/**
 * OpenDominion\Models\HeroTournamentBattle
 *
 * @property int $id
 * @property int $hero_tournament_id
 * @property int $hero_battle_id
 * @property int $round_number
 * @property-read \OpenDominion\Models\HeroTournament $tournament
 * @property-read \OpenDominion\Models\HeroBattle $battle
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\HeroTournamentBattle newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\HeroTournamentBattle newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\HeroTournamentBattle query()
 * @mixin \Eloquent
 */
class HeroTournamentBattle extends AbstractModel
{
    protected $table = 'hero_tournament_battles';

    public $timestamps = false;

    public function tournament()
    {
        return $this->belongsTo(HeroTournament::class);
    }

    public function battle()
    {
        return $this->belongsTo(HeroBattle::class);
    }
}
