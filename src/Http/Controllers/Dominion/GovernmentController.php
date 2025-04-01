<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\GovernmentHelper;
use OpenDominion\Http\Requests\Dominion\Actions\GovernmentActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\GuardMembershipActionRequest;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\Actions\GovernmentActionService;
use OpenDominion\Services\Dominion\Actions\GuardMembershipActionService;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Services\Dominion\ProtectionService;

class GovernmentController extends AbstractDominionController
{
    public function getIndex(Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $guardMembershipService = app(GuardMembershipService::class);

        if (!$dominion->round->hasAssignedRealms()) {
            $request->session()->flash('alert-warning', 'You cannot access this page until realm assignment is finished.');
            return redirect()->back();
        }

        $dominions = $dominion->realm->dominions()
            ->with([
                'race',
                'realm',
                'monarchVote',
            ])
            ->get()
            ->sortByDesc(function ($dominion) {
                return app(LandCalculator::class)->getTotalLand($dominion);
            });

        // todo: move all guardMembershipService calls to template?
        return view('pages.dominion.government', [
            'dominions' => $dominions,
            'realms' => $dominion->round->realms()->active()->get()->sortBy('number'),
            'monarch' => $dominion->realm->monarch,
            'canJoinGuards' => $guardMembershipService->canJoinGuards($dominion),
            'isRoyalGuardApplicant' => $guardMembershipService->isRoyalGuardApplicant($dominion),
            'isEliteGuardApplicant' => $guardMembershipService->isEliteGuardApplicant($dominion),
            'isBlackGuardApplicant' => $guardMembershipService->isBlackGuardApplicant($dominion),
            'isLeavingBlackGuard' => $guardMembershipService->isLeavingBlackGuard($dominion),
            'isRoyalGuardMember' => $guardMembershipService->isRoyalGuardMember($dominion),
            'isEliteGuardMember' => $guardMembershipService->isEliteGuardMember($dominion),
            'isBlackGuardMember' => $guardMembershipService->isBlackGuardMember($dominion),
            'isGuardMember' => $guardMembershipService->isGuardMember($dominion),
            'hoursBeforeRoyalGuardMember' => $guardMembershipService->getHoursBeforeRoyalGuardMember($dominion),
            'hoursBeforeEliteGuardMember' => $guardMembershipService->getHoursBeforeEliteGuardMember($dominion),
            'hoursBeforeBlackGuardMember' => $guardMembershipService->getHoursBeforeBlackGuardMember($dominion),
            'hoursBeforeLeaveRoyalGuard' => $guardMembershipService->getHoursBeforeLeaveRoyalGuard($dominion),
            'hoursBeforeLeaveEliteGuard' => $guardMembershipService->getHoursBeforeLeaveEliteGuard($dominion),
            'hoursBeforeLeaveBlackGuard' => $guardMembershipService->getHoursBeforeLeaveBlackGuard($dominion),
            'hoursBeforeLeavingBlackGuard' => $guardMembershipService->getHoursBeforeLeavingBlackGuard($dominion),
            'governmentHelper' => app(GovernmentHelper::class),
            'governmentService' => app(GovernmentService::class),
            'landCalculator' => app(LandCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),
            'protectionService' => app(ProtectionService::class),
            'rangeCalculator' => app(RangeCalculator::class)
        ]);
    }

    public function postMonarch(Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $governmentActionService = app(GovernmentActionService::class);

        $vote = $request->get('monarch');
        $governmentActionService->voteForMonarch($dominion, $vote);

        $request->session()->flash('alert-success', 'Your vote has been cast!');
        return redirect()->route('dominion.government');
    }

    public function postRealm(Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $governmentActionService = app(GovernmentActionService::class);

        $motd = $request->get('realm_motd');
        $name = $request->get('realm_name');

        try {
            $governmentActionService->updateRealm($dominion, $motd, $name);
        } catch (GameException $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Your realm has been updated!');
        return redirect()->route('dominion.government');
    }

    public function postAppointments(GovernmentActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $governmentActionService = app(GovernmentActionService::class);

        $appointee = Dominion::find($request->get('appointee'));
        $role = $request->get('role');

        try {
            $governmentActionService->setAppointments($dominion, $appointee, $role);
        } catch (GameException $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Your appointments have been updated!');
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

    public function postJoinBlackGuard(GuardMembershipActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $guardActionService = app(GuardMembershipActionService::class);

        try {
            $result = $guardActionService->joinBlackGuard($dominion);
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

    public function postLeaveBlackGuard(GuardMembershipActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $guardActionService = app(GuardMembershipActionService::class);

        try {
            $result = $guardActionService->leaveBlackGuard($dominion);
        } catch (GameException $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.government');
    }

    public function postCancelLeaveBlackGuard(GuardMembershipActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $guardActionService = app(GuardMembershipActionService::class);

        try {
            $result = $guardActionService->cancelLeaveBlackGuard($dominion);
        } catch (GameException $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.government');
    }

    public function postDeclareWar(Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $governmentActionService = app(GovernmentActionService::class);

        $realm_number = $request->get('realm_number');

        try {
            $governmentActionService->declareWar($dominion, $realm_number);
        } catch (GameException $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', "You have declared WAR on realm #{$realm_number}!");
        return redirect()->route('dominion.government');
    }

    public function postCancelWar(Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $governmentActionService = app(GovernmentActionService::class);

        try {
            $governmentActionService->cancelWar($dominion);
        } catch (GameException $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Your realm is no longer at war.');
        return redirect()->route('dominion.government');
    }

    public function postAdvisors(Request $request)
    {
        $newValues = $request->input('realmadvisors') ?? [];
        $selectedDominion = $this->getSelectedDominion();
        $settings = ($selectedDominion->settings ?? []);
        $settings['realmadvisors'] = [];

        foreach ($selectedDominion->realm->dominions as $dominion) {
            if (!in_array($dominion->id, $newValues)) {
                $settings['realmadvisors'][$dominion->id] = false;
            } else {
                $settings['realmadvisors'][$dominion->id] = true;
            }
        }

        $selectedDominion->settings = $settings;
        $selectedDominion->save();
        $request->session()->flash('alert-success', 'Your advisors have been updated.');
        return redirect()->route('dominion.government');
    }
}
