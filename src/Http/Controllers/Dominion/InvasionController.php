<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Http\Requests\Dominion\Actions\InvadeActionRequest;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\Actions\InvadeActionService;
use OpenDominion\Services\Dominion\ProtectionService;
use Throwable;

class InvasionController extends AbstractDominionController
{
    public function getInvade()
    {
        return view('pages.dominion.invade', [
            'landCalculator' => app(LandCalculator::class),
            'protectionService' => app(ProtectionService::class),
            'rangeCalculator' => app(RangeCalculator::class),
            'unitHelper' => app(UnitHelper::class),
        ]);
    }

    public function postInvade(InvadeActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $invasionActionService = app(InvadeActionService::class);

        try {
            $result = $invasionActionService->invade(
                $dominion,
                Dominion::findOrFail($request->get('target_dominion')),
                $request->get('unit')
            );

        } catch (Throwable $e) {
            return redirect()->back()
//                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        // analytics event

        $request->session()->flash(('alert-' . ($result['alert-type'] ?? 'success')), $result['message']);
        return redirect()->to($result['redirect'] ?? route('dominion.invade'));
    }
}
