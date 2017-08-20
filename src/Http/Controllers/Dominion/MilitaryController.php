<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Exception;
use OpenDominion\Contracts\Calculators\Dominion\Actions\TrainingCalculator;
use OpenDominion\Contracts\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Contracts\Services\AnalyticsService;
use OpenDominion\Contracts\Services\Dominion\Actions\Military\ChangeDraftRateActionService;
use OpenDominion\Contracts\Services\Dominion\Actions\Military\TrainActionService;
use OpenDominion\Contracts\Services\Dominion\Queue\TrainingQueueService;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Http\Requests\Dominion\Actions\Military\ChangeDraftRateActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\Military\TrainActionRequest;
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
}
