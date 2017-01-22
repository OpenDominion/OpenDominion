<?php

namespace OpenDominion\Http\Controllers;

use DB;
use Exception;
use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Exceptions\BadInputException;
use OpenDominion\Exceptions\NotEnoughResourcesException;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Http\Requests\Dominion\Actions\ExploreActionRequest;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Repositories\RealmRepository;
use OpenDominion\Services\Actions\ConstructionActionService;
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

        } catch (Exception $e) {
            return response('Unauthorized', 401);
        }

        return redirect(route('dominion.status'));
    }

    // Dominion

    public function getStatus()
    {
        $landCalculator = app()->make(LandCalculator::class);
        $populationCalculator = app()->make(PopulationCalculator::class);

        // todo: make status view a partial for here + other dominion status and include stuff like OOP here

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
        $dominionQueueService = app()->make(DominionQueueService::class);
        $dominionQueueService->setDominion($this->getSelectedDominion());

        return view('pages.dominion.explore', compact(
            'landHelper',
            'landCalculator',
            'dominionQueueService'
        ));
    }

    public function postExplore(ExploreActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $explorationActionService = app()->make(ExplorationActionService::class);

        try {
            $result = $explorationActionService->explore($dominion, $request->get('explore'));

        } catch (BadInputException $e) {
            $request->session()->flash('alert-danger', 'Exploration was not begun due to bad input.');

            return redirect(route('dominion.explore'))
                ->withInput($request->all());

        } catch (NotEnoughResourcesException $e) {
            $totalLandToExplore = array_sum($request->get('explore'));
            $request->session()->flash('alert-danger', "You do not have enough platinum/draftees to explore for {$totalLandToExplore} acres.");

            return redirect(route('dominion.explore'))
                ->withInput($request->all());

        } catch (Exception $e) {
            $request->session()->flash('alert-danger', 'Something went wrong. Please try again later.');

            return redirect(route('dominion.explore'))
                ->withInput($request->all());
        }

        $message = sprintf(
            'Exploration begun at a cost of %s platinum and %s draftees. Your orders for exploration disheartens the military, and morale drops %s%%.',
            number_format($result['platinumCost']),
            number_format($result['drafteeCost']),
            number_format($result['moraleDrop'])
        );

        $request->session()->flash('alert-success', $message);
        return redirect(route('dominion.explore'));
    }

    public function getConstruction()
    {
        $buildingHelper = app()->make(BuildingHelper::class);
        $buildingCalculator = app()->make(BuildingCalculator::class);
        $landCalculator = app()->make(LandCalculator::class);
        $dominionQueueService = app()->make(DominionQueueService::class);
        $dominionQueueService->setDominion($this->getSelectedDominion());

        return view('pages.dominion.construction', compact(
            'buildingHelper',
            'buildingCalculator',
            'landCalculator',
            'dominionQueueService'
        ));
    }

    public function postConstruction(/*ConstructionActionRequest*/ Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $constructionActionService = app()->make(ConstructionActionService::class);

        try {
            $result = $constructionActionService->construct($dominion, $request->get('construct'));

        } catch (BadInputException $e) {
            $request->session()->flash('alert-danger', 'Construction was not started due to bad input.');

            return redirect(route('dominion.construction'))
                ->withInput($request->all());

        } catch (NotEnoughResourcesException $e) {
            $totalBuildingsToConstruct = array_sum($request->get('construct'));
            $request->session()->flash('alert-danger', "You do not have enough platinum/lumber/barren land to construct {$totalBuildingsToConstruct} buildings.");

            return redirect(route('dominion.construction'))
                ->withInput($request->all());

        } catch (Exception $e) {
            $request->session()->flash('alert-danger', 'Something went wrong. Please try again later.');

            return redirect(route('dominion.construction'))
                ->withInput($request->all());
        }

        $message = sprintf(
            'Construction started at a cost of %s platinum and %s lumber.',
            number_format($result['platinumCost']),
            number_format($result['lumberCost'])
        );

        $request->session()->flash('alert-success', $message);
        return redirect(route('dominion.construction'));
    }

    // Black Ops

    // Comms?

    // Realm

    public function getRealm(Realm $realm = null)
    {
        $landCalculator = app()->make(LandCalculator::class);
        $networthCalculator = app()->make(NetworthCalculator::class);

        if (!$realm->exists) {
            $realm = $this->getSelectedDominion()->realm;
        }

        $dominions = $realm->dominions()/*->with('race')*/->orderBy('networth', 'desc')->get();

        // Todo: optimize this hacky hacky stuff
        $prevRealm = DB::table('realms')
            ->where('number', '<', $realm->number)
            ->orderBy('number', 'desc')
            ->limit(1)
            ->get();

        $nextRealm = DB::table('realms')
            ->where('number', '>', $realm->number)
            ->orderBy('number', 'asc')
            ->limit(1)
            ->get();

        return view('pages.dominion.realm', compact(
            'landCalculator',
            'networthCalculator',
            'realm',
            'dominions',
            'prevRealm',
            'nextRealm'
        ));
    }

    // Misc?

    // Other Dominions

    public function getOtherStatus(Dominion $dominion)
    {
        $landCalculator = app()->make(LandCalculator::class);
        $populationCalculator = app()->make(PopulationCalculator::class);

        $landCalculator->setDominion($dominion);
        $populationCalculator->setDominion($dominion);

        return view('pages.dominion.other.status', compact(
            'dominion',
            'landCalculator',
            'populationCalculator'
        ));
    }

    /**
     * @return Dominion
     */
    protected function getSelectedDominion()
    {
        $dominionSelectorService = app()->make(DominionSelectorService::class);
        return $dominionSelectorService->getUserSelectedDominion();
    }
}
