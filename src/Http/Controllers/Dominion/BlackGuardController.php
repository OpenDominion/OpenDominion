<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenDominion\Calculators\Dominion\EspionageCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\EspionageHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Http\Requests\Dominion\Actions\CastSpellActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\PerformEspionageRequest;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\Actions\EspionageActionService;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Services\Dominion\ProtectionService;

class BlackGuardController extends AbstractDominionController
{
    public function getBlackGuard(Request $request)
    {
        $targetDominion = $request->input('dominion');

        $dominion = $this->getSelectedDominion();
        $guardMembershipService = app(GuardMembershipService::class);

        return view('pages.dominion.black-guard', [
            'governmentService' => app(GovernmentService::class),
            'espionageCalculator' => app(EspionageCalculator::class),
            'espionageHelper' => app(EspionageHelper::class),
            'guardMembershipService' => $guardMembershipService,
            'hoursBeforeBlackGuardMember' => $guardMembershipService->getHoursBeforeBlackGuardMember($dominion),
            'hoursBeforeLeaveBlackGuard' => $guardMembershipService->getHoursBeforeLeaveBlackGuard($dominion),
            'hoursBeforeLeavingBlackGuard' => $guardMembershipService->getHoursBeforeLeavingBlackGuard($dominion),
            'isBlackGuardApplicant' => $guardMembershipService->isBlackGuardApplicant($dominion),
            'isBlackGuardMember' => $guardMembershipService->isBlackGuardMember($dominion),
            'isLeavingBlackGuard' => $guardMembershipService->isLeavingBlackGuard($dominion),
            'landCalculator' => app(LandCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'protectionService' => app(ProtectionService::class),
            'rangeCalculator' => app(RangeCalculator::class),
            'spellCalculator' => app(SpellCalculator::class),
            'spellHelper' => app(SpellHelper::class),
            'targetDominion' => $targetDominion,
        ]);
    }

    public function postCastSpell(CastSpellActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $spellActionService = app(SpellActionService::class);

        try {
            $result = $spellActionService->castSpell(
                $dominion,
                $request->get('spell'),
                ($request->has('target_dominion') ? Dominion::findOrFail($request->get('target_dominion')) : null)
            );
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash(('alert-' . ($result['alert-type'] ?? 'success')), $result['message']);

        return redirect()
            ->to(route('dominion.black-guard'))
            ->with('target_dominion', $request->get('target_dominion'));
    }

    public function postPerformEspionage(PerformEspionageRequest $request)
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

        return redirect()
            ->to(route('dominion.black-guard'))
            ->with('target_dominion', $request->get('target_dominion'));
    }
}
