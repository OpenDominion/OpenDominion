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
use OpenDominion\Helpers\ValuablesHelper;
use OpenDominion\Http\Requests\Dominion\Actions\PerformEspionageRequest;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Valuable;
use OpenDominion\Services\Dominion\Actions\EspionageActionService;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Services\Dominion\ProtectionService;

class EspionageController extends AbstractDominionController
{
    public function getEspionage(Request $request)
    {
        $targetDominion = $request->input('dominion');
        $dominion = $this->getSelectedDominion();

        $valuablesDiscovered = Valuable::query()
            ->where('source_dominion_id', $dominion->id)
            ->whereIn('status', [
                Valuable::STATUS_DISCOVERED,
                Valuable::STATUS_INVESTIGATING,
                Valuable::STATUS_LISTED_FOR_TRANSFER,
                Valuable::STATUS_TRANSFERRED,
            ])
            ->with('targetDominion')
            ->orderByDesc('discovered_at')
            ->get();

        $valuablesStolen = Valuable::query()
            ->where('source_dominion_id', $dominion->id)
            ->where('status', Valuable::STATUS_STOLEN)
            ->with('targetDominion')
            ->orderByDesc('stolen_at')
            ->get();

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
            'valuablesDiscovered' => $valuablesDiscovered,
            'valuablesStolen' => $valuablesStolen,
            'valuablesHelper' => app(ValuablesHelper::class),
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
                Dominion::withGameRelations()->findOrFail($request->get('target_dominion'))
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
