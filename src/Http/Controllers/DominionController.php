<?php

namespace OpenDominion\Http\Controllers;

use Carbon\Carbon;
use DB;
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
        // todo: refactor this into ExplorationActionService

        $dominion = $this->getSelectedDominion();

//        $landHelper = app()->make(LandHelper::class);
        $landCalculator = app()->make(LandCalculator::class);
//        $explorationActionService = app()->make(ExplorationActionService::class);

        $totalLandToExplore = array_sum($request->get('explore'));

        if ($totalLandToExplore === 0) {
            $request->session()->flash('alert-danger', 'Exploration was not begun due to bad input.');

            return redirect(route('dominion.explore'))
                ->withInput($request->all());
        }

        $availableLand = $landCalculator->getExplorationMaxAfford();

        if ($totalLandToExplore > $landCalculator->getExplorationMaxAfford()) {
            $request->session()->flash('alert-danger', "You do not have enough platinum/draftees to explore for {$totalLandToExplore} acres.");

            return redirect(route('dominion.explore'))
                ->withInput($request->all());
        }

        $newMorale = max(0, $dominion->morale - ($totalLandToExplore * $landCalculator->getExplorationMoraleDrop($totalLandToExplore)));
        $moraleDrop = ($dominion->morale - $newMorale);

        $platinumCost = ($landCalculator->getExplorationPlatinumCost() * $totalLandToExplore);
        $newPlatinum = ($dominion->resource_platinum - $platinumCost);

        $drafteeCost = ($landCalculator->getExplorationDrafteeCost() * $totalLandToExplore);
        $newDraftee = ($dominion->military_draftees - $drafteeCost);

        $explore = array_map('intval', $request->get('explore'));

        $dateTime = new Carbon;

        DB::beginTransaction();

        DB::table('dominions')
            ->where('id', $dominion->id)
            ->update([
                'morale' => $newMorale,
                'resource_platinum' => $newPlatinum,
                'military_draftees' => $newDraftee,
            ]);

        // Check for existing queue
        $existingQueueRows = DB::table('queue_exploration')
            ->where([
                'dominion_id' => $dominion->id,
                'hours' => 12,
            ])->get(['*']);

        foreach ($existingQueueRows as $row) {
            $explore[$row->land_type] += $row->amount;
        }

        foreach ($explore as $landType => $amount) {
            if ($amount === 0) {
                continue;
            }

            $where = [
                'dominion_id' => $dominion->id,
                'land_type' => $landType,
                'hours' => 12,
            ];

            $data = [
                'amount' => $amount,
                'updated_at' => $dateTime,
            ];

            if ($existingQueueRows->isEmpty()) {
                $data['created_at'] = $dateTime;
            }

            DB::table('queue_exploration')
                ->updateOrInsert($where, $data);
        }

        DB::commit();

        $message = sprintf(
            'Exploration begun at a cost of %s platinum and %s draftees. Your orders for exploration disheartens the military, and morale drops %s%%.',
            number_format($platinumCost),
            number_format($drafteeCost),
            number_format($moraleDrop)
        );

        $request->session()->flash('alert-success', $message);
        return redirect(route('dominion.explore'));
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
