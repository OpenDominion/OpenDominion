<?php

namespace OpenDominion\Http\Controllers\Staff\Administrator;

use Carbon\Carbon;
use Illuminate\Http\Request;
use OpenDominion\Factories\RoundFactory;
use OpenDominion\Helpers\TechHelper;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundLeague;

class RoundController extends AbstractController
{
    public function __construct(protected RoundFactory $roundFactory)
    {
    }

    public function getIndex()
    {
        $rounds = Round::with('league')
            ->orderBy('start_date', 'desc')
            ->get();

        return view('pages.staff.administrator.rounds.index', [
            'rounds' => $rounds,
        ]);
    }

    public function getCreate()
    {
        return view('pages.staff.administrator.rounds.create', [
            'leagues' => RoundLeague::orderBy('key')->get(),
            'defaults' => $this->defaults(),
        ]);
    }

    public function postCreate(Request $request)
    {
        $validated = $this->validateRound($request);

        $round = $this->roundFactory->create(
            RoundLeague::findOrFail($validated['round_league_id']),
            new Carbon($validated['start_date']),
            (int) $validated['pack_size'],
            (int) $validated['players_per_race'],
            (bool) $validated['mixed_alignment'],
            (int) $validated['tech_version'],
            $validated['discord_guild_id'] ?? null,
            (int) $validated['duration_in_days'],
            $validated['description'] ?? null,
        );

        if (!empty($validated['name'])) {
            $round->name = $validated['name'];
            $round->save();
        }

        $request->session()->flash('alert-success', 'Round created successfully.');

        return redirect()->route('staff.administrator.rounds.show', $round);
    }

    public function getShow(Round $round)
    {
        $round->load('league');

        return view('pages.staff.administrator.rounds.show', [
            'round' => $round,
        ]);
    }

    public function getEdit(Round $round)
    {
        return view('pages.staff.administrator.rounds.edit', [
            'round' => $round,
            'leagues' => RoundLeague::orderBy('key')->get(),
        ]);
    }

    public function postEdit(Request $request, Round $round)
    {
        $validated = $this->validateRound($request);

        $startDate = new Carbon($validated['start_date']);
        $duration = (int) $validated['duration_in_days'];

        $round->update([
            'round_league_id' => $validated['round_league_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addDays($duration),
            'pack_size' => (int) $validated['pack_size'],
            'players_per_race' => (int) $validated['players_per_race'],
            'mixed_alignment' => (bool) $validated['mixed_alignment'],
            'tech_version' => (int) $validated['tech_version'],
            'discord_guild_id' => $validated['discord_guild_id'] ?? null,
        ]);

        $request->session()->flash('alert-success', 'Round updated successfully.');

        return redirect()->route('staff.administrator.rounds.show', $round);
    }

    protected function validateRound(Request $request): array
    {
        return $request->validate([
            'round_league_id' => 'required|integer|exists:round_leagues,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:128',
            'start_date' => 'required|date',
            'duration_in_days' => 'required|integer|min:1',
            'pack_size' => 'required|integer|min:1',
            'players_per_race' => 'required|integer|min:0',
            'mixed_alignment' => 'required|boolean',
            'tech_version' => 'required|integer|min:1',
            'discord_guild_id' => 'nullable|string|max:255',
        ]);
    }

    protected function defaults(): array
    {
        return [
            'duration_in_days' => RoundFactory::ROUND_DURATION_IN_DAYS,
            'pack_size' => 5,
            'players_per_race' => 2,
            'mixed_alignment' => 1,
            'tech_version' => TechHelper::CURRENT_VERSION,
        ];
    }
}
