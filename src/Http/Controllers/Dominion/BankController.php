<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Exception;
use OpenDominion\Contracts\Calculators\Dominion\Actions\BankingCalculator;
use OpenDominion\Contracts\Services\AnalyticsService;
use OpenDominion\Contracts\Services\Dominion\Actions\BankActionService;
use OpenDominion\Http\Requests\Dominion\Actions\BankActionRequest;
use OpenDominion\Services\AnalyticsService\Event;

class BankController extends AbstractDominionController
{
    public function getBank()
    {
        $dominion = $this->getSelectedDominion();
        $resources = app(BankingCalculator::class)->getResources($dominion);

        return view('pages.dominion.bank', compact('resources'));
    }

    public function postBank(BankActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $bankActionService = app(BankActionService::class);

        try {
            $bankActionService->exchange(
                $dominion,
                $request->get('source'),
                $request->get('target'),
                $request->get('amount')
            );

        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $message = 'Your resources have been exchanged.';

        // todo: fire laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new Event(
            'dominion',
            'Bank',
            '', // todo: make null?
            $request->get('amount')
        ));

        $request->session()->flash('alert-success', $message);
        return redirect()->route('dominion.bank');
    }
}
