<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Exception;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Http\Requests\Dominion\Actions\ImproveActionRequest;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\Actions\ImproveActionService;

class ImprovementController extends AbstractDominionController
{
    public function getImprovements()
    {
        return view('pages.dominion.improvements', [
            'improvementCalculator' => app(ImprovementCalculator::class),
            'improvementHelper' => app(ImprovementHelper::class),
        ]);
    }

    public function postImprovements(ImproveActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $improveActionService = app(ImproveActionService::class);

        try {
            $result = $improveActionService->improve(
                $dominion,
                $request->get('resource'),
                $request->get('improve')
            );

        } catch (Exception $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        // todo: fire laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsEvent(
            'dominion',
            'improve',
            null,
            array_sum($request->get('improve'))
        ));

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.improvements');
    }
}
