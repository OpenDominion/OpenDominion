<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Exception;
use OpenDominion\Http\Requests\Dominion\Actions\GuardMembershipActionRequest;
use OpenDominion\Services\Dominion\GuardService;
use OpenDominion\Services\Dominion\Actions\GuardActionService;

class GuardController extends AbstractDominionController
{
    public function getGuardMembership()
    {
        $dominion = $this->getSelectedDominion();
        $guardService = app(GuardService::class);

        return view('pages.dominion.guard-membership', [
            'isRoyalGuardApplicant' => $guardService->isRoyalGuardApplicant($dominion),
            'isEliteGuardApplicant' => $guardService->isEliteGuardApplicant($dominion),
            'isRoyalGuardMember' => $guardService->isRoyalGuardMember($dominion),
            'isEliteGuardMember' => $guardService->isEliteGuardMember($dominion),
            'hoursBeforeRoyalGuardMember' => $guardService->getHoursBeforeRoyalGuardMember($dominion),
            'hoursBeforeEliteGuardMember' => $guardService->getHoursBeforeEliteGuardMember($dominion),
            'hoursBeforeLeaveRoyalGuard' => $guardService->getHoursBeforeLeaveRoyalGuard($dominion),
            'hoursBeforeLeaveEliteGuard' => $guardService->getHoursBeforeLeaveEliteGuard($dominion)
        ]);
    }

    public function postJoinRoyalGuard(GuardMembershipActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $guardActionService = app(GuardActionService::class);

        try {
            $result = $guardActionService->joinRoyalGuard($dominion);
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.guard-membership');
    }

    public function postLeaveRoyalGuard(GuardMembershipActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $guardActionService = app(GuardActionService::class);

        try {
            $result = $guardActionService->leaveRoyalGuard($dominion);
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.guard-membership');
    }

    public function postJoinEliteGuard(GuardMembershipActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $guardActionService = app(GuardActionService::class);

        try {
            $result = $guardActionService->joinEliteGuard($dominion);
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.guard-membership');
    }

    public function postLeaveEliteGuard(GuardMembershipActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $guardActionService = app(GuardActionService::class);

        try {
            $result = $guardActionService->leaveEliteGuard($dominion);
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.guard-membership');
    }
}
