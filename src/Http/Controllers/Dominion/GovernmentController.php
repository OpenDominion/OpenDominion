<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Exception;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Http\Requests\Dominion\Actions\GovernmentActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\GuardMembershipActionRequest;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Services\Dominion\Actions\GovernmentActionService;
use OpenDominion\Services\Dominion\Actions\GuardMembershipActionService;

class GovernmentController extends AbstractDominionController
{
    public function getIndex()
    {
        $dominion = $this->getSelectedDominion();
        $guardMembershipService = app(GuardMembershipService::class);

        return view('pages.dominion.government', [
            'monarch' => $dominion->realm->monarch,
            'canJoinGuards' => $guardMembershipService->canJoinGuards($dominion),
            'isRoyalGuardApplicant' => $guardMembershipService->isRoyalGuardApplicant($dominion),
            'isEliteGuardApplicant' => $guardMembershipService->isEliteGuardApplicant($dominion),
            'isRoyalGuardMember' => $guardMembershipService->isRoyalGuardMember($dominion),
            'isEliteGuardMember' => $guardMembershipService->isEliteGuardMember($dominion),
            'hoursBeforeRoyalGuardMember' => $guardMembershipService->getHoursBeforeRoyalGuardMember($dominion),
            'hoursBeforeEliteGuardMember' => $guardMembershipService->getHoursBeforeEliteGuardMember($dominion),
            'hoursBeforeLeaveRoyalGuard' => $guardMembershipService->getHoursBeforeLeaveRoyalGuard($dominion),
            'hoursBeforeLeaveEliteGuard' => $guardMembershipService->getHoursBeforeLeaveEliteGuard($dominion),
            'landCalculator' => app(LandCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class)
        ]);
    }

    public function postMonarch(GovernmentActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $governmentActionService = app(GovernmentActionService::class);

        $vote = $request->get('monarch');
        $governmentActionService->voteForMonarch($dominion, $vote);

        $request->session()->flash('alert-success', 'Your vote has been cast');
        return redirect()->route('dominion.government');
    }

    public function postRealmName(GovernmentActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $governmentActionService = app(GovernmentActionService::class);

        $name = $request->get('realm_name');
        $governmentActionService->changeRealmName($dominion, $name);

        $request->session()->flash('alert-success', 'Your realm name has been changed');
        return redirect()->route('dominion.government');
    }

    public function postJoinRoyalGuard(GuardMembershipActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $guardActionService = app(GuardMembershipActionService::class);

        try {
            $result = $guardActionService->joinRoyalGuard($dominion);
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.government');
    }

    public function postLeaveRoyalGuard(GuardMembershipActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $guardActionService = app(GuardMembershipActionService::class);

        try {
            $result = $guardActionService->leaveRoyalGuard($dominion);
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.government');
    }

    public function postJoinEliteGuard(GuardMembershipActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $guardActionService = app(GuardMembershipActionService::class);

        try {
            $result = $guardActionService->joinEliteGuard($dominion);
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.government');
    }

    public function postLeaveEliteGuard(GuardMembershipActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $guardActionService = app(GuardMembershipActionService::class);

        try {
            $result = $guardActionService->leaveEliteGuard($dominion);
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.government');
    }
}
