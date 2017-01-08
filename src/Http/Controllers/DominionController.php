<?php

namespace OpenDominion\Http\Controllers;

use OpenDominion\Models\Dominion;
use OpenDominion\Services\DominionSelectorService;

class DominionController extends AbstractController
{
    public function postSelect(Dominion $dominion)
    {
        $dominionSelectorService = app()->make(DominionSelectorService::class);

        try {
            $dominionSelectorService->selectUserDominion($dominion);

        } catch (\Exception $e) {
            return response('Unauthorized', 401);
        }

        return redirect(route('dominion.status'));
    }

    // Dominion

    public function getStatus()
    {
        return view('pages.dominion.status');
    }

    public function getAdvisors()
    {
        return view('pages.dominion.advisors');
    }

    public function getAdvisorsProduction()
    {
        // todo
    }

    public function getAdvisorsMilitary()
    {
        // todo
    }

    public function getAdvisorsLand()
    {
        // todo
    }

    public function getAdvisorsConstruction()
    {
        // todo
    }

    // Actions

    public function getExplore()
    {
        return view('pages.dominion.explore');
    }

    public function getConstruction()
    {
        return view('pages.dominion.construction');
    }

    // Black Ops

    // Comms?

    // Realm

    // Misc?
}
