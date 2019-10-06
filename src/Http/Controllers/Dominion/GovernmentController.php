<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\Actions\GovernmentActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\GuardMembershipActionRequest;
use OpenDominion\Services\Dominion\Actions\GovernmentActionService;
use OpenDominion\Services\Dominion\Actions\GuardMembershipActionService;
use OpenDominion\Services\Dominion\GuardMembershipService;

class GovernmentController extends AbstractDominionController
{
    public function getIndex()
    {
        $dominion = $this->getSelectedDominion();
        $guardMembershipService = app(GuardMembershipService::class);

        $dominions = $dominion->realm->dominions()
            ->with([
                'race',
                'race.perks',
                'race.units',
                'race.units.perks',
                'monarchVote',
            ])
            ->orderBy('name')
            ->get();

        return view('pages.dominion.government', [
            'dominions' => $dominions,
            'monarch' => $dominion->realm->monarch,
            'canJoinGuards' => $guardMembershipService->canJoinGuards($dominion),
            'isRoyalGuardApplicant' => $guardMembershipService->isRoyalGuardApplicant($dominion),
            'isEliteGuardApplicant' => $guardMembershipService->isEliteGuardApplicant($dominion),
            'isRoyalGuardMember' => $guardMembershipService->isRoyalGuardMember($dominion),
            'isEliteGuardMember' => $guardMembershipService->isEliteGuardMember($dominion),
            'isGuardMember' => $guardMembershipService->isGuardMember($dominion),
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

        $request->session()->flash('alert-success', 'Your vote has been cast!');
        return redirect()->route('dominion.government');
    }

    public function postRealm(GovernmentActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $governmentActionService = app(GovernmentActionService::class);

        $motd = $request->get('realm_motd');
        $name = $request->get('realm_name');
        $governmentActionService->updateRealm($dominion, $motd, $name);

        $request->session()->flash('alert-success', 'Your realm has been updated!');
        return redirect()->route('dominion.government');
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
