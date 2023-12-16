<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Http\Requests\Dominion\Actions\AutomationActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\DailyBonusesLandActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\DailyBonusesPlatinumActionRequest;
use OpenDominion\Services\Dominion\Actions\DailyBonusesActionService;
use OpenDominion\Services\Dominion\AutomationService;
use OpenDominion\Services\Dominion\LogParserService;

class DailyBonusesController extends AbstractDominionController
{
    public function getBonuses(Request $request)
    {
        $dominion = $this->getSelectedDominion();

        $logParserService = app(LogParserService::class);
        $log = $logParserService->writeLog($dominion);

        return view('pages.dominion.bonuses', [
            'allowedActions' => AutomationService::DAILY_ACTIONS,
            'log' => $log
        ]);
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

    public function getAutomatedActions(Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $buildingHelper = app(BuildingHelper::class);
        $landHelper = app(LandHelper::class);
        $spellHelper = app(SpellHelper::class);
        $unitHelper = app(UnitHelper::class);

        $buildings = $buildingHelper->getBuildingTypes();
        $landTypes = $landHelper->getLandTypes();
        $spells = $spellHelper->getSpells($dominion->race, 'self')
            ->forget(['amplify_magic', 'ares_call', 'fools_gold'])
            ->sortBy('key');
        $unitTypes = $unitHelper->getUnitTypes();

        return view('pages.dominion.automation', [
            'buildingHelper' => $buildingHelper,
            'spellHelper' => $spellHelper,
            'unitHelper' => $unitHelper,
            'allowedActions' => AutomationService::DAILY_ACTIONS,
            'buildings' => $buildings,
            'landTypes' => $landTypes,
            'spells' => $spells,
            'unitTypes' => $unitTypes,
        ]);
    }

    public function postAutomatedActions(AutomationActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        if ($dominion->protection_ticks_remaining) {
            $request->session()->flash('alert-danger', 'You cannot schedule any actions while you have protection ticks remaining.');
            return redirect()->route('dominion.bonuses.actions');
        }
        $automationService = app(AutomationService::class);

        $config = [
            'tick' => $request->get('tick'),
            'value' => [
                'action' => $request->get('action'),
                'key' => $request->get('key'),
                'amount' => $request->get('amount')
            ]
        ];

        try {
            $automationService->setConfig($dominion, $config);
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Action was successfully scheduled.');
        return redirect()->route('dominion.bonuses.actions');
    }

    public function postDeleteAutomatedAction(Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $automationService = app(AutomationService::class);

        try {
            $automationService->deleteAction($dominion, $request->get('tick'), $request->get('key'));
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Action was successfully deleted.');
        return redirect()->route('dominion.bonuses.actions');
    }
}
