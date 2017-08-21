<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Exception;
use OpenDominion\Contracts\Calculators\Dominion\Actions\TrainingCalculator;
use OpenDominion\Contracts\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Contracts\Services\AnalyticsService;
use OpenDominion\Contracts\Services\Dominion\Actions\Military\ChangeDraftRateActionService;
use OpenDominion\Contracts\Services\Dominion\Actions\Military\TrainActionService;
use OpenDominion\Contracts\Services\Dominion\Actions\ReleaseActionService;
use OpenDominion\Contracts\Services\Dominion\Queue\TrainingQueueService;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Http\Requests\Dominion\Actions\Military\ChangeDraftRateActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\Military\TrainActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\ReleaseActionRequest;
use OpenDominion\Services\AnalyticsService\Event;

class MilitaryController extends AbstractDominionController
{
    public function getMilitary()
    {
        return view('pages.dominion.military', [
            'populationCalculator' => app(PopulationCalculator::class),
            'trainingCalculator' => app(TrainingCalculator::class),
            'trainingQueueService' => app(TrainingQueueService::class),
            'unitHelper' => app(UnitHelper::class),
        ]);
    }

    public function postChangeDraftRate(ChangeDraftRateActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $changeDraftRateActionService = app(ChangeDraftRateActionService::class);

        try {
            $result = $changeDraftRateActionService->changeDraftRate($dominion, $request->get('draft_rate'));

        } catch (Exception $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $message = sprintf(
            'Draft rate changed to %d%%.',
            $result['draftRate']
        );

        // todo: fire laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new Event(
            'dominion',
            'military.change-draft-rate',
            '',
            $result['draftRate']
        ));

        $request->session()->flash('alert-success', $message);
        return redirect()->route('dominion.military');
    }

    public function postTrain(TrainActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $militaryTrainActionService = app(TrainActionService::class);

        try {
            $result = $militaryTrainActionService->train($dominion, $request->get('train'));

        } catch (Exception $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $message = $result['message']; // todo: ActionResponse

        // todo: fire laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new Event(
            'dominion',
            'military.train',
            '',
            null //$result['totalUnits']
        ));

        $request->session()->flash('alert-success', $message);
        return redirect()->route('dominion.military');
    }

    public function getRelease()
    {
        return view('pages.dominion.release', [
            'unitHelper' => app(UnitHelper::class),
        ]);
    }

    public function postRelease(ReleaseActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $releaseActionService = app(ReleaseActionService::class);

        try {
            $result = $releaseActionService->release($dominion, $request->get('release'));

        } catch (Exception $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        // You cannot release more draftees than you have.
        // Military release was not completed due to bad input.
        // Military release aborted due to bad input. (all 0)

        // You successfully released 1 draftees into the peasantry.
        // You successfully released 1 Archer into draftees.
        // You successfully released 1 draftees into the peasantry and 1 Archer, 1 Spies into draftees.
        // You destroyed 1 Guard Towers.
        // You successfully released 1 draftees into the peasantry and 1 Archer into draftees. You also destroyed 1 Guard Towers.

//        $message = sprintf(
//            'Destruction of %s buildings is complete.',
//            number_format($result['totalBuildingsDestroyed'])
//        );

        $message = '';

        // todo: laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new Event(
            'dominion',
            'release',
            '', // todo: make null everywhere where ''
            null //$result['totalBuildingsDestroyed']
        ));

        $request->session()->flash('alert-success', $message);
        return redirect()->route('dominion.military.release');

    }
}
