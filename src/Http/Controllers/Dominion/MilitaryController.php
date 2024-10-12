<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\Actions\TrainingCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\ResourceHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Http\Requests\Dominion\Actions\Military\ChangeDraftRateActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\Military\TrainActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\ReleaseActionRequest;
use OpenDominion\Mappers\Dominion\InfoMapper;
use OpenDominion\Services\Dominion\Actions\Military\ChangeDraftRateActionService;
use OpenDominion\Services\Dominion\Actions\Military\TrainActionService;
use OpenDominion\Services\Dominion\Actions\ReleaseActionService;
use OpenDominion\Services\Dominion\QueueService;

class MilitaryController extends AbstractDominionController
{
    public function getMilitary()
    {
        return view('pages.dominion.military', [
            'infoMapper' => app(InfoMapper::class),
            'landHelper' => app(LandHelper::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'populationCalculator' => app(PopulationCalculator::class),
            'queueService' => app(QueueService::class),
            'resourceHelper' => app(ResourceHelper::class),
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

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.military');
    }

    public function postTrain(TrainActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $militaryTrainActionService = app(TrainActionService::class);

        try {
            $result = $militaryTrainActionService->train($dominion, $request->get('train'));

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

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

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.military.release');

    }
}
