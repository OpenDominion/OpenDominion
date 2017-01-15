<?php

namespace OpenDominion\Http\Controllers;

use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Actions\ExplorationActionService;
use OpenDominion\Services\DominionQueueService;
use OpenDominion\Services\DominionSelectorService;

class DominionController extends AbstractController
{
    public function postSelect(Dominion $dominion)
    {
        $dominionSelectorService = app()->make(DominionSelectorService::class);

        try {
            $dominionSelectorService->selectUserDominion($dominion);

        } catch (\Exception $e) {
            return response('Unauthorized', 401);
        }

        return redirect(route('dominion.status'));
    }

    // Dominion

    public function getStatus()
    {
        $landCalculator = app()->make(LandCalculator::class);
        $populationCalculator = app()->make(PopulationCalculator::class);

        return view('pages.dominion.status', compact(
            'landCalculator',
            'populationCalculator'
        ));
    }

    public function getAdvisors()
    {
        return redirect(route('dominion.advisors.production'));
    }

    public function getAdvisorsProduction()
    {
        $populationCalculator = app()->make(PopulationCalculator::class);
        $productionCalculator = app()->make(ProductionCalculator::class);

        return view('pages.dominion.advisors.production', compact(
            'populationCalculator',
            'productionCalculator'
        ));
    }

    public function getAdvisorsMilitary()
    {
        return view('pages.dominion.advisors.military');
    }

    public function getAdvisorsLand()
    {
        return view('pages.dominion.advisors.land');
    }

    public function getAdvisorsConstruction()
    {
        return view('pages.dominion.advisors.construction');
    }

    // Actions

    public function getExplore()
    {
        $landHelper = app()->make(LandHelper::class);
        $landCalculator = app()->make(LandCalculator::class);
        $dominionQueueService = app()->make(DominionQueueService::class, [$this->getSelectedDominion()]);

        return view('pages.dominion.explore', compact(
            'landHelper',
            'landCalculator',
            'dominionQueueService'
        ));
    }

    public function postExplore(Request $request)
    {
//        $landHelper = app()->make(LandHelper::class);
//        $landCalculator = app()->make(LandCalculator::class);
//        $explorationActionService = app()->make(ExplorationActionService::class);

        dd($request);
    }

    public function getConstruction()
    {
        return view('pages.dominion.construction');
    }

    // Black Ops

    // Comms?

    // Realm

    // Misc?

    /**
     * @return Dominion
     */
    protected function getSelectedDominion()
    {
        $dominionSelectorService = app()->make(DominionSelectorService::class);
        return $dominionSelectorService->getUserSelectedDominion();
    }
}
