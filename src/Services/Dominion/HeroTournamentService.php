<?php

namespace OpenDominion\Services\Dominion;

use Illuminate\Support\Collection;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\HeroTournament;
use OpenDominion\Models\HeroTournamentBattle;
use OpenDominion\Models\HeroTournamentParticipant;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\HeroBattleService;

class HeroTournamentService
{
    /** @var HeroBattleService */
    protected $heroBattleService;

    /**
     * HeroTournamentService constructor.
     */
    public function __construct()
    {
        $this->heroBattleService = app(HeroBattleService::class);
    }

    protected const DEFAULT_NAME = 'The Grand Tournament';

    public function createTournament(Round $round, string $name = self::DEFAULT_NAME): HeroTournament
    {
        $tournament = HeroTournament::create([
            'round_id' => $round->id,
            'name' => $name,
            'current_round_number' => 1,
        ]);

        foreach ($round->activeDominions()->human()->get() as $dominion) {
            if ($dominion->hero !== null) {
                HeroTournamentParticipant::create([
                    'hero_id' => $dominion->hero->id,
                    'hero_tournament_id' => $tournament->id,
                ]);
            }
        }

        $this->handleMatchups($tournament);

        return $tournament;
    }

    public function handleMatchups(HeroTournament $tournament): void
    {
        $participants = $tournament->participants()
            ->where('eliminated', false)
            ->inRandomOrder()
            ->get()
            ->sortByDesc('wins');

        foreach ($participants->chunk(2) as $pairing) {
            $participants = $pairing->values();
            if ($participants->count() == 1) {
                // Participant gets a bye
                continue;
            }
            $heroBattle = $this->heroBattleService->createBattle($participants[0]->hero->dominion, $participants[1]->hero->dominion);
            HeroTournamentBattle::create([
                'hero_tournament_id' => $tournament->id,
                'hero_battle_id' => $heroBattle->id,
                'round_number' => $tournament->current_round_number,
            ]);
        }
    }

    public function processTournaments(Round $round): void
    {
        $tournaments = HeroTournament::query()
            ->where('round_id', $round->id)
            ->where('finished', false)
            ->get();

        foreach ($tournaments as $tournament) {
            $this->checkRoundEnded($tournament);
        }
    }

    public function checkRoundEnded(HeroTournament $tournament): void
    {
        if ($tournament->battles->where('finished', false)->count() > 0) {
            return;
        }

        $this->processEliminations($tournament);
        $finished = $this->checkTournamentEnded($tournament);
        if (!$finished) {
            $tournament->increment('current_round_number');
            $this->handleMatchups($tournament);
        }
    }

    public function processEliminations(HeroTournament $tournament): void
    {
        foreach ($tournament->participants()->where('eliminated', false)->get() as $participant) {
            $losses = $participant->losses + rfloor($participant->draws / 2);
            if ($losses >= 2) {
                $participant->eliminated = true;
                $participant->save();
            }
        }

        $participantsRemaining = $tournament->participants->where('eliminated', false)->count();
        $tournament->participants()
            ->where('eliminated', true)
            ->where('standing', null)
            ->update(['standing' => $participantsRemaining + 1]);
    }

    public function checkTournamentEnded(HeroTournament $tournament): bool
    {
        $activeParticipants = $tournament->participants()->where('eliminated', false)->get();
        if ($activeParticipants->count() == 1) {
            $winner = $activeParticipants->first();
            $winner->update(['standing' => 1]);
            $tournament->finished = true;
            $tournament->winner_dominion_id = $winner->hero->dominion_id;
            $tournament->save();
        }

        return $tournament->finished;
    }
}
