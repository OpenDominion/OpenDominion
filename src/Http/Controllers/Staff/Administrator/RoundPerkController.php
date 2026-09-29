<?php

namespace OpenDominion\Http\Controllers\Staff\Administrator;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use OpenDominion\Helpers\RoundPerkHelper;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Http\Requests\Staff\Administrator\SaveRoundPerkRequest;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundPerk;
use OpenDominion\Services\RoundPerkService;

class RoundPerkController extends AbstractController
{
    public function __construct(
        protected RoundPerkHelper $roundPerkHelper,
        protected RoundPerkService $roundPerkService
    ) {
    }

    public function getCreate(Round $round): View
    {
        return view('pages.staff.administrator.rounds.perks.create', [
            'round' => $round,
            'roundPerkHelper' => $this->roundPerkHelper,
        ]);
    }

    public function postCreate(SaveRoundPerkRequest $request, Round $round): RedirectResponse
    {
        $this->roundPerkService->create($round, $request->validated());

        $request->session()->flash('alert-success', 'Round perk created successfully.');

        return redirect()->route('staff.administrator.rounds.show', $round);
    }

    public function getEdit(Round $round, RoundPerk $perk): View
    {
        abort_unless($perk->round_id === $round->id, 404);

        return view('pages.staff.administrator.rounds.perks.edit', [
            'round' => $round,
            'perk' => $perk,
            'roundPerkHelper' => $this->roundPerkHelper,
        ]);
    }

    public function postEdit(SaveRoundPerkRequest $request, Round $round, RoundPerk $perk): RedirectResponse
    {
        abort_unless($perk->round_id === $round->id, 404);

        $this->roundPerkService->update($perk, $request->validated());

        $request->session()->flash('alert-success', 'Round perk updated successfully.');

        return redirect()->route('staff.administrator.rounds.show', $round);
    }

    public function postDelete(Request $request, Round $round, RoundPerk $perk): RedirectResponse
    {
        abort_unless($perk->round_id === $round->id, 404);

        $this->roundPerkService->delete($perk);

        $request->session()->flash('alert-success', 'Round perk deleted successfully.');

        return redirect()->route('staff.administrator.rounds.show', $round);
    }
}
