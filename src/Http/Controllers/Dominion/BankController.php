<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\Actions\BankingCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\Actions\BankActionRequest;
use OpenDominion\Services\Dominion\Actions\BankActionService;

class BankController extends AbstractDominionController
{
    public function getBank()
    {
        return view('pages.dominion.bank', [
            'bankingCalculator' => app(BankingCalculator::class),
        ]);
    }

    public function postBank(BankActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $bankActionService = app(BankActionService::class);

        try {
            $result = $bankActionService->exchange(
                $dominion,
                $request->get('source'),
                $request->get('target'),
                $request->get('amount')
            );

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.bank');
    }
}
