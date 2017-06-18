<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Exception;
use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Exceptions\BadInputException;
use OpenDominion\Exceptions\DominionLockedException;
use OpenDominion\Exceptions\NotEnoughResourcesException;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Services\Actions\ConstructionActionService;
use OpenDominion\Services\Actions\DestroyActionService;
use OpenDominion\Services\AnalyticsService;
use OpenDominion\Services\DominionQueueService;
use Symfony\Component\HttpFoundation\Response;

class ConstructionController extends AbstractDominionController
{
    public function getConstruction()
    {
        $buildingHelper = app(BuildingHelper::class);
        $buildingCalculator = app(BuildingCalculator::class);
        $landCalculator = app(LandCalculator::class);
        $dominionQueueService = app(DominionQueueService::class);
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
        $constructionActionService = app(ConstructionActionService::class);

        try {
            $result = $constructionActionService->construct($dominion, $request->get('construct'));

        } catch (DominionLockedException $e) {
            $request->session()->flash('alert-danger', 'Construction was not started due to the dominion being locked.');

            return redirect()->route('dominion.construction')
                ->setStatusCode(Response::HTTP_FORBIDDEN)
                ->withInput($request->all());

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
        $analyticsService = app(AnalyticsService::class);
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
        $buildingHelper = app(BuildingHelper::class);
        $buildingCalculator = app(BuildingCalculator::class);
        $landCalculator = app(LandCalculator::class);

        return view('pages.dominion.destroy', compact(
            'buildingHelper',
            'buildingCalculator',
            'landCalculator'
        ));
    }

    public function postDestroy(Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $destroyActionService = app(DestroyActionService::class);

        try {
            $result = $destroyActionService->destroy($dominion, $request->get('destroy'));

        } catch (DominionLockedException $e) {
            $request->session()->flash('alert-danger', 'The destruction was not completed due to the dominion being locked.');

            return redirect()->route('dominion.destroy')
                ->setStatusCode(Response::HTTP_FORBIDDEN)
                ->withInput($request->all());

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
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsService\Event(
            'dominion',
            'destroy',
            '',
            $result['totalBuildingsDestroyed']
        ));

        $request->session()->flash('alert-success', $message);
        return redirect()->route('dominion.destroy');
    }
}
