<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Exception;
use OpenDominion\Calculators\Dominion\Actions\TrainingCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Http\Requests\Dominion\Actions\Military\ChangeDraftRateActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\Military\TrainActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\ReleaseActionRequest;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\Actions\Military\ChangeDraftRateActionService;
use OpenDominion\Services\Dominion\Actions\Military\TrainActionService;
use OpenDominion\Services\Dominion\Actions\ReleaseActionService;
use OpenDominion\Services\Dominion\QueueService;

class MilitaryController extends AbstractDominionController
{
    public function getMilitary()
    {
        return view('pages.dominion.military', [
            'populationCalculator' => app(PopulationCalculator::class),
            'queueService' => app(QueueService::class),
            'trainingCalculator' => app(TrainingCalculator::class),
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

        // todo: fire laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsEvent(
            'dominion',
            'military.change-draft-rate',
            '',
            $result['data']['draftRate']
        ));

        $request->session()->flash('alert-success', $result['message']);
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

        // todo: fire laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsEvent(
            'dominion',
            'military.train',
            '',
            null //$result['totalUnits']
        ));

        $request->session()->flash('alert-success', $result['message']);
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

        // todo: laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsEvent(
            'dominion',
            'release',
            null, // todo: make null everywhere where ''
            $result['data']['totalTroopsReleased']
        ));

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.military.release');

    }
}
