<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Services\Dominion\AutomationService;
use OpenDominion\Services\Dominion\LogParserService;
use OpenDominion\Traits\DominionGuardsTrait;

class ProtectionController extends AbstractDominionController
{
    use DominionGuardsTrait;

    public function getBuildings(Request $request)
    {
        $dominion = $this->getSelectedDominion();

        if (!$dominion->isBuildingPhase()) {
            return redirect()->route('dominion.status');
        }

        $buildingHelper = app(BuildingHelper::class);
        $landCalculator = app(LandCalculator::class);
        $buildingsByLandType = collect($buildingHelper->getBuildingTypesByRace($dominion->race));

        return view('pages.dominion.protection.buildings', [
            'buildingHelper' => $buildingHelper,
            'buildingsByLandType' => $buildingsByLandType,
            'landCalculator' => $landCalculator,
        ]);
    }

    public function postBuildings(Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $buildingHelper = app(BuildingHelper::class);
        $landCalculator = app(LandCalculator::class);
        $buildingsByLandType = collect($buildingHelper->getBuildingTypesByRace($dominion->race));

        try {
            $this->guardLockedDominion($dominion, true);

            if (!$dominion->isBuildingPhase()) {
                throw new GameException('You have already selected your starting buildings.');
            }

            $data = $request->get('construct') ?? [];
            $data = Arr::only($data, array_map(function ($value) {
                return "building_{$value}";
            }, $buildingHelper->getBuildingTypes()));
            $data = array_map('\intval', $data);

            $automationService = app(AutomationService::class);
            $automationService->processStartingBuildings($dominion, $data);
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Your buildings have been selected. Click next to confirm.');
        return view('pages.dominion.protection.buildings', [
            'buildingHelper' => $buildingHelper,
            'buildingsByLandType' => $buildingsByLandType,
            'landCalculator' => $landCalculator,
        ]);
    }

    public function getImportLog(Request $request)
    {
        $dominion = $this->getSelectedDominion();

        try {
            $this->guardLockedDominion($dominion, true);

            // Sims cannot be loaded after the round has started
            if ($dominion->round->hasStarted()) {
                throw new GameException('You cannot import a log after the round has started.');
            }

            if ($dominion->protection_ticks_remaining == 0) {
                throw new GameException('You must have protection ticks remaining to use this feature.');
            }
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        return view('pages.dominion.protection.import-log', [
            'logErrors' => []
        ]);
    }

    public function postImportLog(Request $request)
    {
        $dominion = $this->getSelectedDominion();

        try {
            $this->guardLockedDominion($dominion, true);

            // Sims cannot be loaded after the round has started
            if ($dominion->round->hasStarted()) {
                throw new GameException('You cannot import a log after the round has started.');
            }

            if ($dominion->protection_ticks_remaining == 0) {
                throw new GameException('You must have protection ticks remaining to use this feature.');
            }

            if (!$request->get('log')) {
                throw new GameException('No data to import.');
            }

            $logParserService = app(LogParserService::class);
            [$logErrors, $logJSON] = $logParserService->parseLog($dominion, $request->get('log'));
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        if ($logErrors) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors($logErrors);
        }

        $request->session()->flash('alert-success', 'Log successfully validated and ready for processing.');
        return view('pages.dominion.protection.automate', [
            'log' => $request->get('log'),
            'logJSON' => $logJSON
        ]);
    }

    public function postAutomateProtection(Request $request)
    {
        $dominion = $this->getSelectedDominion();

        try {
            $this->guardLockedDominion($dominion, true);

            // Sims cannot be loaded after the round has started
            if ($dominion->round->hasStarted()) {
                throw new GameException('You cannot import a log after the round has started.');
            }

            if ($dominion->protection_ticks_remaining == 0) {
                throw new GameException('You must have protection ticks remaining to use this feature.');
            }

            $protection = json_decode($request->get('logJSON'), true);
            $automationService = app(AutomationService::class);
            $automationService->processLog($dominion, $protection);
        } catch (GameException $e) {
            if ($dominion->protection_ticks_remaining == 0) {
                return redirect()->route('dominion.status')
                    ->withErrors([$e->getMessage(), 'You can manually perform the actions for this hour']);
            }
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage(), 'You can manually perform the actions for this hour and reimport the remaining hours']);
        }

        $request->session()->flash('alert-success', 'Log successfully processed.');
        return redirect()->route('dominion.status');
    }
}
