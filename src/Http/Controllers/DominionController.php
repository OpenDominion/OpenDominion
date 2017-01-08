<?php

namespace OpenDominion\Http\Controllers;

use OpenDominion\Models\Dominion;
use OpenDominion\Services\DominionSelectorService;

class DominionController extends AbstractController
{
    public function postPlay(Dominion $dominion)
    {
        $dominionSelectorService = app()->make(DominionSelectorService::class);

        try {
            $dominionSelectorService->selectUserDominion($dominion);

        } catch (\Exception $e) {
            return response('Unauthorized', 401);
        }
        // Check that round is active
        // todo

        return redirect(route('dominion.status'));
    }

    public function getStatus()
    {
        return view('pages.dominion.status');
    }
}
