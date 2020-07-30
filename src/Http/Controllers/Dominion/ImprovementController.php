<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Http\Requests\Dominion\Actions\ImproveActionRequest;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\Actions\ImproveActionService;

class ImprovementController extends AbstractDominionController
{
    public function getImprovements(Request $request)
    {
        $dominion = $this->getSelectedDominion();

        $preferredResource = $dominion->getSetting('preferredInvestmentResource');
        if(!$preferredResource)
        {
            $preferredResource = 'gems';
        }

        return view('pages.dominion.improvements', [
            'improvementCalculator' => app(ImprovementCalculator::class),
            'improvementHelper' => app(ImprovementHelper::class),
            'selectedResource' => $request->query('resource', $preferredResource),
            'preferredResource' => $preferredResource,
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

        } catch (GameException $e) {
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
        return redirect()->route('dominion.improvements', [
            'resource' => $request->get('resource'),
        ]);
    }

    public function postPreferredResource(Request $request)
    {
        $newResource = $request->get('preferredresource');
        $selectedDominion = $this->getSelectedDominion();
        $settings = ($selectedDominion->settings ?? []);
        $settings['preferredInvestmentResource'] = $newResource;

        $selectedDominion->settings = $settings;
        $selectedDominion->save();
        $request->session()->flash('alert-success', 'Your preferred resource has been changed.');
        return redirect()->route('dominion.improvements');
    }
}
