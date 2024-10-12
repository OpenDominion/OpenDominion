<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\Actions\RezoningCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\Actions\RezoneActionRequest;
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

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.rezone');
    }
}
