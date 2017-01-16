<?php

namespace OpenDominion\Http\Controllers;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Http\Requests\Dominion\Actions\ExploreActionRequest;
use OpenDominion\Models\Dominion;
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

    public function postExplore(ExploreActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();

//        $landHelper = app()->make(LandHelper::class);
        $landCalculator = app()->make(LandCalculator::class);
//        $explorationActionService = app()->make(ExplorationActionService::class);

        $totalLandToExplore = array_sum($request->get('explore'));

        if ($totalLandToExplore === 0) {
            // redirect to get explore

            // 'Exploration was not begun due to bad input.'
        }

        $availableLand = $landCalculator->getExplorationMaxAfford();

        if ($totalLandToExplore > $landCalculator->getExplorationMaxAfford()) {
            // error

            // 'You do not have enough platinum/draftees to explore for $total acres.'
        }

        $platinumCost = ($landCalculator->getExplorationPlatinumCost() * $totalLandToExplore);
        $drafteeCost = ($landCalculator->getExplorationDrafteeCost() * $totalLandToExplore);
        $newMorale = max(0, $dominion->morale - ($totalLandToExplore * $landCalculator->getExplorationMoraleDrop($totalLandToExplore)));
        $moraleDrop = ($dominion->morale - $newMorale);

        // trans start

        // reduce platinum/draftees/morale

        // insert/update queue_exploration

        // trans commit

//        // todo: optimize?
//        $tmp = array();
//        foreach ($explore as $land_type => $amount) {
//            if ($amount == 0) {
//                continue;
//            }
//
//            $tmp[] = ($amount . ' ' . $land_types[$land_type]);
//        }
//        $explore_string = strtolower(strrev(preg_replace(strrev('/, /'), strrev(' and '), strrev(implode(', ', $tmp)), 1)));
//        "Exploration for {$explore_string} begun at a cost of " . number_format($platinum_cost) . " platinum and " . number_format($draftee_cost) . " draftees. Your orders for exploration disheartens the military, and morale drops {$morale_drop}%."

        // redirect to get explore w/ message

        dd($request->get('explore'));
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
