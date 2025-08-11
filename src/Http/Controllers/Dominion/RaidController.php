<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\RaidCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\RaidHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Http\Requests\Dominion\Actions\RaidActionRequest;
use OpenDominion\Models\Raid;
use OpenDominion\Models\RaidObjective;
use OpenDominion\Models\RaidObjectiveTactic;
use OpenDominion\Services\Dominion\Actions\RaidActionService;
use OpenDominion\Traits\DominionGuardsTrait;

class RaidController extends AbstractDominionController
{
    use DominionGuardsTrait;

    public function getRaids()
    {
        $raidCalculator = app(RaidCalculator::class);
        $raidHelper = app(RaidHelper::class);
        $selectedDominion = $this->getSelectedDominion();
        $selectedRealm = $selectedDominion->realm;

        $raids = $selectedDominion->round->raids->sortBy('order');

        return view('pages.dominion.raids', compact(
            'raidCalculator',
            'raidHelper',
            'raids',
            'selectedRealm'
        ));
    }

    public function getRaidObjective(RaidObjective $objective)
    {
        $landCalculator = app(LandCalculator::class);
        $militaryCalculator = app(MilitaryCalculator::class);
        $raidCalculator = app(RaidCalculator::class);
        $raidHelper = app(RaidHelper::class);
        $unitHelper = app(UnitHelper::class);
        $selectedDominion = $this->getSelectedDominion();
        $selectedRealm = $selectedDominion->realm;

        return view('pages.dominion.raid-objective', compact(
            'objective',
            'landCalculator',
            'militaryCalculator',
            'raidCalculator',
            'raidHelper',
            'unitHelper',
            'selectedRealm'
        ));
    }

    public function postRaidTactic(RaidObjectiveTactic $tactic, RaidActionRequest $request)
    {
        $selectedDominion = $this->getSelectedDominion();
        $raidActionService = app(RaidActionService::class);

        try {
            $result = $raidActionService->performAction($selectedDominion, $tactic, $request->validated());
            $request->session()->flash('alert-success', $result['message']);
        } catch (GameException $e) {
            $request->session()->flash('alert-danger', $e->getMessage());
        }

        if (isset($result['redirect'])) {
            return redirect($result['redirect']);
        }

        return redirect()->route('dominion.raids.objective', $tactic->objective);
    }

    public function getRaidLeaderboard(RaidObjective $objective)
    {
        $raidCalculator = app(RaidCalculator::class);
        $selectedDominion = $this->getSelectedDominion();
        $selectedRealm = $selectedDominion->realm;

        $leaderboard = $raidCalculator->getRealmsLeaderboard($objective);
        $totalScore = $raidCalculator->getObjectiveScore($objective);

        return view('pages.dominion.raid-leaderboard', [
            'objective' => $objective,
            'raidCalculator' => $raidCalculator,
            'selectedRealm' => $selectedRealm,
            'leaderboard' => $leaderboard,
            'totalScore' => $totalScore,
        ]);
    }
}
