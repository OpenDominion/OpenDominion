<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Exception;
use OpenDominion\Calculators\Dominion\Actions\RezoningCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Http\Requests\Dominion\Actions\RezoneActionRequest;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\Actions\RezoneActionService;

class RezoneController extends AbstractDominionController
{
    public function getRezone()
    {
        return view('pages.dominion.rezone', [
            'landCalculator' => app(LandCalculator::class),
            'rezoningCalculator' => app(RezoningCalculator::class),
        ]);
    }

    public function postRezone(RezoneActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $rezoneActionService = app(RezoneActionService::class);

        try {
            $result = $rezoneActionService->rezone(
                $dominion,
                $request->get('remove'),
                $request->get('add')
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
            'rezone',
            '', // todo: make null?
            array_sum($request->get('remove'))
        ));

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.rezone');
    }
}
