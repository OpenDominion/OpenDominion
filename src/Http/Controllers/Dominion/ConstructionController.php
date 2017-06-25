<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Exception;
use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Contracts\Calculators\Dominion\Actions\ConstructionCalculator;
use OpenDominion\Contracts\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Exceptions\BadInputException;
use OpenDominion\Exceptions\DominionLockedException;
use OpenDominion\Exceptions\NotEnoughResourcesException;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Services\Actions\ConstructionActionService;
use OpenDominion\Services\Actions\DestroyActionService;
use OpenDominion\Services\AnalyticsService;
use OpenDominion\Services\DominionQueueService;

class ConstructionController extends AbstractDominionController
{
    public function getConstruction()
    {
        return view('pages.dominion.construction', [
            'buildingCalculator' => app(BuildingCalculator::class),
            'buildingHelper' => app(BuildingHelper::class),
            'constructionCalculator' => app(ConstructionCalculator::class),
            'dominionQueueService' => app(DominionQueueService::class),
            'landCalculator' => app(LandCalculator::class),
        ]);
    }

    public function postConstruction(/*ConstructionActionRequest*/ Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $constructionActionService = app(ConstructionActionService::class);

        try {
            $result = $constructionActionService->construct($dominion, $request->get('construct'));

        } catch (DominionLockedException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors(['Construction was not started due to the dominion being locked.']);

        } catch (BadInputException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors(['Construction was not started due to bad input.']);

        } catch (NotEnoughResourcesException $e) {
            $totalBuildingsToConstruct = array_sum($request->get('construct'));

            return redirect()->back()
                ->withInput($request->all())
                ->withErrors(["You do not have enough platinum, lumber and/or barren land to construct {$totalBuildingsToConstruct} buildings."]);

        } catch (Exception $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors(['Something went wrong. Please try again later.']);
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
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors(['The destruction was not completed due to the dominion being locked.']);

        } catch (BadInputException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors(['The destruction was not completed due to incorrect input.']);

        } catch (Exception $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors(['Something went wrong. Please try again later.']);
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
