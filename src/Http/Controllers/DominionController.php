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
use OpenDominion\Services\Actions\ConstructionActionService;
use OpenDominion\Services\Actions\DestroyActionService;
use OpenDominion\Services\Actions\ExplorationActionService;
use OpenDominion\Services\AnalyticsService;
use OpenDominion\Services\DominionQueueService;
use OpenDominion\Services\DominionSelectorService;
use Symfony\Component\HttpFoundation\Response;

class DominionController extends AbstractController
{
    public function postSelect(Dominion $dominion)
    {
        $dominionSelectorService = resolve(DominionSelectorService::class);

        try {
            $dominionSelectorService->selectUserDominion($dominion);

        } catch (Exception $e) {
            return response('Unauthorized', 401);
        }

        // todo: fire laravel event
        $analyticsService = resolve(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsService\Event(
            'dominion',
            'select'
        ));

        return redirect()->route('dominion.status');
    }

    // Dominion

    public function getStatus()
    {
        $landCalculator = resolve(LandCalculator::class);
        $populationCalculator = resolve(PopulationCalculator::class);

        // todo: make status view a partial for here + other dominion status and include stuff like OOP here

        return view('pages.dominion.status', compact(
            'landCalculator',
            'populationCalculator'
        ));
    }

    public function getAdvisors()
    {
        return redirect()->route('dominion.advisors.production');
    }

    public function getAdvisorsProduction()
    {
        $populationCalculator = resolve(PopulationCalculator::class);
        $productionCalculator = resolve(ProductionCalculator::class);

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
        $landHelper = resolve(LandHelper::class);
        $landCalculator = resolve(LandCalculator::class);
        $dominionQueueService = resolve(DominionQueueService::class);

        return view('pages.dominion.advisors.land', compact(
            'landHelper',
            'landCalculator',
            'dominionQueueService'
        ));
    }

    public function getAdvisorsConstruction()
    {
        $buildingHelper = resolve(BuildingHelper::class);
        $buildingCalculator = resolve(BuildingCalculator::class);
        $landCalculator = resolve(LandCalculator::class);
        $dominionQueueService = resolve(DominionQueueService::class);

        return view('pages.dominion.advisors.construction', compact(
            'buildingHelper',
            'buildingCalculator',
            'landCalculator',
            'dominionQueueService'
        ));
    }

    // Actions

    public function getExplore()
    {
        $landHelper = resolve(LandHelper::class);
        $landCalculator = resolve(LandCalculator::class);
        $dominionQueueService = resolve(DominionQueueService::class);
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
        $explorationActionService = resolve(ExplorationActionService::class);

        try {
            $result = $explorationActionService->explore($dominion, $request->get('explore'));

        } catch (BadInputException $e) {
            $request->session()->flash('alert-danger', 'Exploration was not begun due to bad input.');

            return redirect()->route('dominion.explore')
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->withInput($request->all());

        } catch (NotEnoughResourcesException $e) {
            $totalLandToExplore = array_sum($request->get('explore'));
            $request->session()->flash('alert-danger', "You do not have enough platinum/draftees to explore for {$totalLandToExplore} acres.");

            return redirect()->route('dominion.explore')
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->withInput($request->all());

        } catch (Exception $e) {
            $request->session()->flash('alert-danger', 'Something went wrong. Please try again later.');

            return redirect()->route('dominion.explore')
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->withInput($request->all());
        }

        $message = sprintf(
            'Exploration begun at a cost of %s platinum and %s draftees. Your orders for exploration disheartens the military, and morale drops %s%%.',
            number_format($result['platinumCost']),
            number_format($result['drafteeCost']),
            number_format($result['moraleDrop'])
        );

        // todo: fire laravel event
        $analyticsService = resolve(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsService\Event(
            'dominion',
            'explore',
            '',
            array_sum($request->get('explore'))
        ));

        $request->session()->flash('alert-success', $message);
        return redirect()->route('dominion.explore');
    }

    public function getConstruction()
    {
        $buildingHelper = resolve(BuildingHelper::class);
        $buildingCalculator = resolve(BuildingCalculator::class);
        $landCalculator = resolve(LandCalculator::class);
        $dominionQueueService = resolve(DominionQueueService::class);
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
        $constructionActionService = resolve(ConstructionActionService::class);

        try {
            $result = $constructionActionService->construct($dominion, $request->get('construct'));

        } catch (BadInputException $e) {
            $request->session()->flash('alert-danger', 'Construction was not started due to bad input.');

            return redirect()->route('dominion.construction')
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->withInput($request->all());

        } catch (NotEnoughResourcesException $e) {
            $totalBuildingsToConstruct = array_sum($request->get('construct'));
            $request->session()->flash('alert-danger', "You do not have enough platinum/lumber/barren land to construct {$totalBuildingsToConstruct} buildings.");

            return redirect()->route('dominion.construction')
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->withInput($request->all());

        } catch (Exception $e) {
            $request->session()->flash('alert-danger', 'Something went wrong. Please try again later.');

            return redirect()->route('dominion.construction')
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->withInput($request->all());
        }

        $message = sprintf(
            'Construction started at a cost of %s platinum and %s lumber.',
            number_format($result['platinumCost']),
            number_format($result['lumberCost'])
        );

        // todo: fire laravel event
        $analyticsService = resolve(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsService\Event(
            'dominion',
            'construct',
            '',
            array_sum($request->get('construct')) // todo: get from $result
        ));

        $request->session()->flash('alert-success', $message);
        return redirect()->route('dominion.construction');
    }

    public function getDestroy()
    {
        $buildingHelper = resolve(BuildingHelper::class);
        $buildingCalculator = resolve(BuildingCalculator::class);
        $landCalculator = resolve(LandCalculator::class);

        return view('pages.dominion.destroy', compact(
            'buildingHelper',
            'buildingCalculator',
            'landCalculator'
        ));
    }

    public function postDestroy(Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $destroyActionService = resolve(DestroyActionService::class);

        try {
            $result = $destroyActionService->destroy($dominion, $request->get('destroy'));

        } catch (BadInputException $e) {
            $request->session()->flash('alert-danger', 'The destruction was not completed due to incorrect input.');

            return redirect()->route('dominion.destroy')
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->withInput($request->all());

        } catch (Exception $e) {
            $request->session()->flash('alert-danger', 'Something went wrong. Please try again later.');

            return redirect()->route('dominion.destroy')
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->withInput($request->all());
        }

        $message = sprintf(
            'Destruction of %s buildings is complete',
            number_format($result['totalBuildingsDestroyed'])
        );

        // todo: laravel event
        $analyticsService = resolve(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsService\Event(
            'dominion',
            'destroy',
            '',
            $result['totalBuildingsDestroyed']
        ));

        $request->session()->flash('alert-success', $message);
        return redirect()->route('dominion.destroy');
    }

    // Black Ops

    // Comms?

    // Realm

    public function getRealm(Realm $realm = null)
    {
        $landCalculator = resolve(LandCalculator::class);
        $networthCalculator = resolve(NetworthCalculator::class);

        if (!$realm->exists) {
            $realm = $this->getSelectedDominion()->realm;
        }

        $dominions = $realm->dominions()/*->with('race')*/->orderBy('networth', 'desc')->get();

        // Todo: optimize this hacky hacky stuff
        $prevRealm = DB::table('realms')
            ->where('number', '<', $realm->number)
            ->orderBy('number', 'desc')
            ->limit(1)
            ->first();

        $nextRealm = DB::table('realms')
            ->where('number', '>', $realm->number)
            ->orderBy('number', 'asc')
            ->limit(1)
            ->first();

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

//    public function getOtherStatus(Dominion $dominion)
//    {
//        $landCalculator = resolve(LandCalculator::class);
//        $populationCalculator = resolve(PopulationCalculator::class);
//
//        $landCalculator->setDominion($dominion);
//        $populationCalculator->setDominion($dominion);
//
//        return view('pages.dominion.other.status', compact(
//            'dominion',
//            'landCalculator',
//            'populationCalculator'
//        ));
//    }

    /**
     * @return Dominion
     */
    protected function getSelectedDominion()
    {
        $dominionSelectorService = resolve(DominionSelectorService::class);
        return $dominionSelectorService->getUserSelectedDominion();
    }
}
