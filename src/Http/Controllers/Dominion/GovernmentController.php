<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\Actions\GuardMembershipActionRequest;
use OpenDominion\Services\Dominion\Actions\GuardMembershipActionService;
use OpenDominion\Services\Dominion\GuardMembershipService;

class GovernmentController extends AbstractDominionController
{
    public function getIndex()
    {
        $dominion = $this->getSelectedDominion();
        $guardMembershipService = app(GuardMembershipService::class);

        return view('pages.dominion.government', [
            'canJoinGuards' => $guardMembershipService->canJoinGuards($dominion),
            'isRoyalGuardApplicant' => $guardMembershipService->isRoyalGuardApplicant($dominion),
            'isEliteGuardApplicant' => $guardMembershipService->isEliteGuardApplicant($dominion),
            'isRoyalGuardMember' => $guardMembershipService->isRoyalGuardMember($dominion),
            'isEliteGuardMember' => $guardMembershipService->isEliteGuardMember($dominion),
            'hoursBeforeRoyalGuardMember' => $guardMembershipService->getHoursBeforeRoyalGuardMember($dominion),
            'hoursBeforeEliteGuardMember' => $guardMembershipService->getHoursBeforeEliteGuardMember($dominion),
            'hoursBeforeLeaveRoyalGuard' => $guardMembershipService->getHoursBeforeLeaveRoyalGuard($dominion),
            'hoursBeforeLeaveEliteGuard' => $guardMembershipService->getHoursBeforeLeaveEliteGuard($dominion)
        ]);
    }

    public function postJoinRoyalGuard(GuardMembershipActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $guardActionService = app(GuardMembershipActionService::class);

        try {
            $result = $guardActionService->joinRoyalGuard($dominion);
        } catch (GameException $e) {
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
        } catch (GameException $e) {
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
        } catch (GameException $e) {
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
        } catch (GameException $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.government');
    }
}
