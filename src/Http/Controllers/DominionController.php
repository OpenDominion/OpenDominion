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

    public function getStatus()
    {
        return view('pages.dominion.status');
    }

    public function getExplore()
    {
        return view('pages.dominion.explore');
    }

    public function getConstruction()
    {
        return view('pages.dominion.construction');
    }
}
