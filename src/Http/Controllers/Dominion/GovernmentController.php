<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\Actions\GovernmentActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\GuardMembershipActionRequest;
use OpenDominion\Services\Dominion\Actions\GovernmentActionService;
use OpenDominion\Services\Dominion\Actions\GuardMembershipActionService;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Services\Dominion\ProtectionService;

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
            ->get()
            ->sortByDesc(function ($dominion) {
                return app(LandCalculator::class)->getTotalLand($dominion);
            });

        // todo: move all guardMembershipService calls to template?
        return view('pages.dominion.government', [
            'dominions' => $dominions,
            'realms' => $dominion->round->realms,
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
            'governmentService' => app(GovernmentService::class),
            'landCalculator' => app(LandCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),
            'protectionService' => app(ProtectionService::class),
            'rangeCalculator' => app(RangeCalculator::class)
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

    public function postDeclareWar(GovernmentActionRequest $request)
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

    public function postCancelWar(GovernmentActionRequest $request)
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

    public function postAdvisors(GovernmentActionRequest $request)
    {
        $newValues = $request->input('realmadvisors');
        $selectedDominion = $this->getSelectedDominion();
        $settings = ($selectedDominion->settings ?? []);
        $settings['realmAdvisors'] = [];

        if($newValues)
        {
            foreach ($selectedDominion->realm->dominions as $dominion)
            {
                if(!in_array($dominion->id, $newValues))
                {

                    continue;
                }

                $settings['realmAdvisors'][$dominion->id] = true;
            }
        }

        $selectedDominion->settings = $settings;
        $selectedDominion->save();
        $request->session()->flash('alert-success', 'Your advisors have been updated.');
        return redirect()->route('dominion.government');
    }
}
