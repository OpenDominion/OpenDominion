<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenDominion\Calculators\Dominion\EspionageCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\EspionageHelper;
use OpenDominion\Http\Requests\Dominion\Actions\PerformEspionageRequest;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\Actions\EspionageActionService;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Services\Dominion\ProtectionService;

class EspionageController extends AbstractDominionController
{
    public function getEspionage(Request $request)
    {
        $targetDominion = $request->input('dominion');

        return view('pages.dominion.espionage', [
            'espionageCalculator' => app(EspionageCalculator::class),
            'espionageHelper' => app(EspionageHelper::class),
            'governmentService' => app(GovernmentService::class),
            'guardMembershipService' => app(GuardMembershipService::class),
            'landCalculator' => app(LandCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'protectionService' => app(ProtectionService::class),
            'rangeCalculator' => app(RangeCalculator::class),
            'targetDominion' => $targetDominion,
        ]);
    }

    public function postEspionage(PerformEspionageRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $espionageActionService = app(EspionageActionService::class);

        try {
            $result = $espionageActionService->performOperation(
                $dominion,
                $request->get('operation'),
                Dominion::findOrFail($request->get('target_dominion'))
            );
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash(('alert-' . ($result['alert-type'] ?? 'success')), $result['message']);

        $bountyRedirect = null;
        if (Str::contains($request->session()->previousUrl(), 'bounty-board')) {
            $bountyRedirect = route('dominion.bounty-board');
        }

        return redirect()
            ->to($bountyRedirect ?? $result['redirect'] ?? route('dominion.espionage'))
            ->with('target_dominion', $request->get('target_dominion'));
    }
}
