<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\Actions\DailyBonusesLandActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\DailyBonusesPlatinumActionRequest;
use OpenDominion\Services\Dominion\Actions\DailyBonusesActionService;

class DailyBonusesController extends AbstractDominionController
{
    public function getBonuses()
    {
        return view('pages.dominion.bonuses');
    }

    public function postBonusesPlatinum(DailyBonusesPlatinumActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $dailyBonusesActionService = app(DailyBonusesActionService::class);

        try {
            $result = $dailyBonusesActionService->claimPlatinum($dominion);
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.bonuses');
    }

    public function postBonusesLand(DailyBonusesLandActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $dailyBonusesActionService = app(DailyBonusesActionService::class);

        try {
            $result = $dailyBonusesActionService->claimLand($dominion);
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.bonuses');
    }
}
