<?php

namespace OpenDominion\Http\Controllers\Staff\Administrator;

use Illuminate\Http\Request;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\HeroTournament;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\HeroTournamentService;

class HeroTournamentController extends AbstractController
{
    /**
     * Display a listing of hero tournaments for the selected round.
     */
    public function getIndex(Request $request)
    {
        $rounds = Round::all()->sortByDesc('start_date');

        $selectedRound = $request->input('round');
        if ($selectedRound) {
            $round = Round::findOrFail($selectedRound);
        } else {
            $round = $rounds->first();
        }

        $tournaments = HeroTournament::with(['round', 'participants', 'winner'])
            ->where('round_id', $round->id)
            ->orderBy('start_date')
            ->get();

        return view('pages.staff.administrator.hero-tournaments.index', [
            'round' => $round,
            'rounds' => $rounds,
            'tournaments' => $tournaments,
        ]);
    }

    /**
     * Show the form for creating a new hero tournament.
     */
    public function getCreate(Request $request)
    {
        $rounds = Round::all()->sortByDesc('start_date');

        $selectedRound = $request->input('round');
        if ($selectedRound) {
            $round = Round::findOrFail($selectedRound);
        } else {
            $round = $rounds->first();
        }

        return view('pages.staff.administrator.hero-tournaments.create', [
            'round' => $round,
            'rounds' => $rounds,
        ]);
    }

    /**
     * Store a newly created hero tournament.
     */
    public function postCreate(Request $request)
    {
        $validated = $request->validate([
            'round_id' => 'required|exists:rounds,id',
            'name' => 'required|string|max:255',
            'start_day' => 'required|integer|min:1',
        ]);

        $round = Round::findOrFail($validated['round_id']);

        $heroTournamentService = app(HeroTournamentService::class);
        $tournament = $heroTournamentService->createTournament(
            $round,
            $validated['start_day'],
            $validated['name']
        );

        $request->session()->flash('alert-success', 'Hero Tournament created successfully.');

        return redirect()->route('staff.administrator.hero-tournaments.show', $tournament);
    }

    /**
     * Display the specified hero tournament.
     */
    public function getShow(HeroTournament $heroTournament)
    {
        $heroTournament->load(['round', 'participants.hero.dominion', 'winner', 'battles']);

        return view('pages.staff.administrator.hero-tournaments.show', [
            'tournament' => $heroTournament,
        ]);
    }

    /**
     * Show the form for editing the specified hero tournament.
     */
    public function getEdit(HeroTournament $heroTournament)
    {
        $rounds = Round::all()->sortByDesc('start_date');

        return view('pages.staff.administrator.hero-tournaments.edit', [
            'tournament' => $heroTournament,
            'rounds' => $rounds,
        ]);
    }

    /**
     * Update the specified hero tournament.
     */
    public function postEdit(Request $request, HeroTournament $heroTournament)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_day' => 'required|integer|min:1',
        ]);

        $round = $heroTournament->round;
        $startDate = $round->start_date->copy()->addDays($validated['start_day'] - 1);

        $heroTournament->update([
            'name' => $validated['name'],
            'start_date' => $startDate,
        ]);

        $request->session()->flash('alert-success', 'Hero Tournament updated successfully.');

        return redirect()->route('staff.administrator.hero-tournaments.show', $heroTournament);
    }

    /**
     * Show the form for confirming deletion of the hero tournament.
     */
    public function getDelete(HeroTournament $heroTournament)
    {
        $heroTournament->load(['participants', 'battles']);

        return view('pages.staff.administrator.hero-tournaments.delete', [
            'tournament' => $heroTournament,
        ]);
    }

    /**
     * Remove the specified hero tournament from storage.
     */
    public function postDelete(Request $request, HeroTournament $heroTournament)
    {
        $roundId = $heroTournament->round_id;

        $heroTournament->participants()->delete();
        $heroTournament->delete();

        $request->session()->flash('alert-success', 'Hero Tournament deleted successfully.');

        return redirect()->route('staff.administrator.hero-tournaments.index', ['round' => $roundId]);
    }
}
