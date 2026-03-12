<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Http\Requests\Dominion\Actions\ImproveActionRequest;
use OpenDominion\Services\Dominion\Actions\ImproveActionService;
use OpenDominion\Services\Dominion\QueueService;

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
            'queueService' => app(QueueService::class),
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

        if (Arr::get($settings, 'preferredInvestmentResource') != $newResource) {
            $settings['preferredInvestmentResource'] = $newResource;
            $selectedDominion->settings = $settings;
            $selectedDominion->save();
        }

        $request->session()->flash('alert-success', 'Your preferred resource has been changed.');
        return redirect()->route('dominion.improvements');
    }
}
