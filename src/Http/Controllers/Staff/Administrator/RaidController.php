<?php

namespace OpenDominion\Http\Controllers\Staff\Administrator;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenDominion\Helpers\RaidHelper;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\Raid;
use OpenDominion\Models\RaidObjective;
use OpenDominion\Models\RaidObjectiveTactic;
use OpenDominion\Models\Round;

class RaidController extends AbstractController
{
    /**
     * Display a listing of raids for the selected round.
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

        $raids = Raid::with(['round', 'objectives'])
            ->where('round_id', $round->id)
            ->get()
            ->sortBy('order');

        return view('pages.staff.administrator.raids.index', [
            'round' => $round,
            'rounds' => $rounds,
            'raids' => $raids,
            'raidHelper' => app(RaidHelper::class),
        ]);
    }

    /**
     * Show the form for creating a new raid.
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

        return view('pages.staff.administrator.raids.create', [
            'round' => $round,
            'rounds' => $rounds,
        ]);
    }

    /**
     * Store a newly created raid.
     */
    public function postCreate(Request $request)
    {
        $validated = $request->validate([
            'round_id' => 'required|exists:rounds,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'reward_resource' => 'required|string',
            'reward_amount' => 'required|integer|min:0',
            'completion_reward_resource' => 'nullable|string',
            'completion_reward_amount' => 'nullable|integer|min:0',
            'start_day' => 'required|integer|min:1',
            'end_day' => 'required|integer|min:1|gt:start_day',
        ]);

        $round = Round::findOrFail($validated['round_id']);

        // Convert day of round to actual date
        $validated['start_date'] = $round->start_date->copy()->addDays($validated['start_day'] - 1);
        $validated['end_date'] = $round->start_date->copy()->addDays($validated['end_day'] - 1);

        // Convert newlines to <br/> tags in description and strip the original newlines
        $validated['description'] = str_replace(["\r\n", "\r", "\n"], '', nl2br($validated['description']));

        unset($validated['start_day'], $validated['end_day']);

        $raid = Raid::create($validated);

        return redirect()
            ->route('staff.administrator.raids.show', $raid)
            ->with('success', 'Raid created successfully.');
    }

    /**
     * Display the specified raid.
     */
    public function getShow(Raid $raid)
    {
        $raid->load(['round', 'objectives']);

        return view('pages.staff.administrator.raids.show', [
            'raid' => $raid,
            'raidHelper' => app(RaidHelper::class),
        ]);
    }

    /**
     * Show the form for editing the specified raid.
     */
    public function getEdit(Raid $raid)
    {
        $rounds = Round::all()->sortByDesc('start_date');

        return view('pages.staff.administrator.raids.edit', [
            'raid' => $raid,
            'rounds' => $rounds,
        ]);
    }

    /**
     * Update the specified raid.
     */
    public function postEdit(Request $request, Raid $raid)
    {
        $validated = $request->validate([
            'round_id' => 'required|exists:rounds,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'reward_resource' => 'required|string',
            'reward_amount' => 'required|integer|min:0',
            'completion_reward_resource' => 'nullable|string',
            'completion_reward_amount' => 'nullable|integer|min:0',
            'start_day' => 'required|integer|min:1',
            'end_day' => 'required|integer|min:1|gt:start_day',
        ]);

        $round = Round::findOrFail($validated['round_id']);

        // Convert day of round to actual date
        $validated['start_date'] = $round->start_date->copy()->addDays($validated['start_day'] - 1);
        $validated['end_date'] = $round->start_date->copy()->addDays($validated['end_day'] - 1);

        // Convert newlines to <br/> tags in description and strip the original newlines
        $validated['description'] = str_replace(["\r\n", "\r", "\n"], '', nl2br($validated['description']));

        unset($validated['start_day'], $validated['end_day']);

        $raid->update($validated);

        return redirect()
            ->route('staff.administrator.raids.show', $raid)
            ->with('success', 'Raid updated successfully.');
    }

    /**
     * Show the form for confirming deletion of the raid.
     */
    public function getDelete(Raid $raid)
    {
        $raid->load(['objectives.tactics']);

        return view('pages.staff.administrator.raids.delete', [
            'raid' => $raid,
        ]);
    }

    /**
     * Remove the specified raid from storage.
     */
    public function postDelete(Raid $raid)
    {
        $roundId = $raid->round_id;
        $raid->delete();

        return redirect()
            ->route('staff.administrator.raids.index', ['round' => $roundId])
            ->with('success', 'Raid deleted successfully.');
    }

    /**
     * Show the form for creating a new objective.
     */
    public function getCreateObjective(Request $request, Raid $raid)
    {
        return view('pages.staff.administrator.raids.objectives.create', [
            'raid' => $raid,
        ]);
    }

    /**
     * Store a newly created objective.
     */
    public function postCreateObjective(Request $request, Raid $raid)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'order' => 'required|integer|min:0',
            'score_required' => 'required|integer|min:1',
            'start_day' => 'required|integer|min:1',
            'end_day' => 'required|integer|min:1|gt:start_day',
        ]);

        $round = $raid->round;

        // Convert day of round to actual date
        $validated['start_date'] = $round->start_date->copy()->addDays($validated['start_day'] - 1);
        $validated['end_date'] = $round->start_date->copy()->addDays($validated['end_day'] - 1);

        // Convert newlines to <br/> tags in description and strip the original newlines
        $validated['description'] = str_replace(["\r\n", "\r", "\n"], '', nl2br($validated['description']));

        unset($validated['start_day'], $validated['end_day']);

        $objective = $raid->objectives()->create($validated);

        return redirect()
            ->route('staff.administrator.raids.objectives.show', [$raid, $objective])
            ->with('success', 'Objective created successfully.');
    }

    /**
     * Display the specified objective.
     */
    public function getShowObjective(Raid $raid, RaidObjective $objective)
    {
        $objective->load(['tactics', 'contributions']);

        return view('pages.staff.administrator.raids.objectives.show', [
            'raid' => $raid,
            'objective' => $objective,
            'raidHelper' => app(RaidHelper::class),
        ]);
    }

    /**
     * Show the form for editing the specified objective.
     */
    public function getEditObjective(Raid $raid, RaidObjective $objective)
    {
        return view('pages.staff.administrator.raids.objectives.edit', [
            'raid' => $raid,
            'objective' => $objective,
        ]);
    }

    /**
     * Update the specified objective.
     */
    public function postEditObjective(Request $request, Raid $raid, RaidObjective $objective)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'order' => 'required|integer|min:0',
            'score_required' => 'required|integer|min:1',
            'start_day' => 'required|integer|min:1',
            'end_day' => 'required|integer|min:1|gt:start_day',
        ]);

        $round = $raid->round;

        // Convert day of round to actual date
        $validated['start_date'] = $round->start_date->copy()->addDays($validated['start_day'] - 1);
        $validated['end_date'] = $round->start_date->copy()->addDays($validated['end_day'] - 1);

        // Convert newlines to <br/> tags in description and strip the original newlines
        $validated['description'] = str_replace(["\r\n", "\r", "\n"], '', nl2br($validated['description']));

        unset($validated['start_day'], $validated['end_day']);

        $objective->update($validated);

        return redirect()
            ->route('staff.administrator.raids.objectives.show', [$raid, $objective])
            ->with('success', 'Objective updated successfully.');
    }

    /**
     * Show the form for confirming deletion of the objective.
     */
    public function getDeleteObjective(Raid $raid, RaidObjective $objective)
    {
        $objective->load(['tactics']);

        return view('pages.staff.administrator.raids.objectives.delete', [
            'raid' => $raid,
            'objective' => $objective,
        ]);
    }

    /**
     * Remove the specified objective from storage.
     */
    public function postDeleteObjective(Raid $raid, RaidObjective $objective)
    {
        $objective->delete();

        return redirect()
            ->route('staff.administrator.raids.show', $raid)
            ->with('success', 'Objective deleted successfully.');
    }

    /**
     * Show the form for creating a new tactic.
     */
    public function getCreateTactic(Request $request, Raid $raid, RaidObjective $objective)
    {
        $raidHelper = app(RaidHelper::class);

        return view('pages.staff.administrator.raids.tactics.create', [
            'raid' => $raid,
            'objective' => $objective,
            'tacticTypes' => $raidHelper->getTypes(),
            'raidHelper' => $raidHelper,
        ]);
    }

    /**
     * Store a newly created tactic.
     */
    public function postCreateTactic(Request $request, Raid $raid, RaidObjective $objective)
    {
        $raidHelper = app(RaidHelper::class);

        $validated = $request->validate([
            'type' => ['required', Rule::in($raidHelper->getTypes())],
            'name' => 'required|string|max:255',
            'attributes' => 'required|json',
            'bonuses' => 'nullable|json',
        ]);

        // Decode JSON for storage
        $validated['attributes'] = json_decode($validated['attributes'], true);
        $validated['bonuses'] = $validated['bonuses'] ? json_decode($validated['bonuses'], true) : [];

        $tactic = $objective->tactics()->create($validated);

        return redirect()
            ->route('staff.administrator.raids.objectives.show', [$raid, $objective])
            ->with('success', 'Tactic created successfully.');
    }

    /**
     * Show the form for editing the specified tactic.
     */
    public function getEditTactic(Raid $raid, RaidObjective $objective, RaidObjectiveTactic $tactic)
    {
        $raidHelper = app(RaidHelper::class);

        return view('pages.staff.administrator.raids.tactics.edit', [
            'raid' => $raid,
            'objective' => $objective,
            'tactic' => $tactic,
            'tacticTypes' => $raidHelper->getTypes(),
            'raidHelper' => $raidHelper,
        ]);
    }

    /**
     * Update the specified tactic.
     */
    public function postEditTactic(Request $request, Raid $raid, RaidObjective $objective, RaidObjectiveTactic $tactic)
    {
        $raidHelper = app(RaidHelper::class);

        $validated = $request->validate([
            'type' => ['required', Rule::in($raidHelper->getTypes())],
            'name' => 'required|string|max:255',
            'attributes' => 'required|json',
            'bonuses' => 'nullable|json',
        ]);

        // Decode JSON for storage
        $validated['attributes'] = json_decode($validated['attributes'], true);
        $validated['bonuses'] = $validated['bonuses'] ? json_decode($validated['bonuses'], true) : [];

        $tactic->update($validated);

        return redirect()
            ->route('staff.administrator.raids.objectives.show', [$raid, $objective])
            ->with('success', 'Tactic updated successfully.');
    }

    /**
     * Show the form for confirming deletion of the tactic.
     */
    public function getDeleteTactic(Raid $raid, RaidObjective $objective, RaidObjectiveTactic $tactic)
    {
        return view('pages.staff.administrator.raids.tactics.delete', [
            'raid' => $raid,
            'objective' => $objective,
            'tactic' => $tactic,
        ]);
    }

    /**
     * Remove the specified tactic from storage.
     */
    public function postDeleteTactic(Raid $raid, RaidObjective $objective, RaidObjectiveTactic $tactic)
    {
        $tactic->delete();

        return redirect()
            ->route('staff.administrator.raids.objectives.show', [$raid, $objective])
            ->with('success', 'Tactic deleted successfully.');
    }
}
