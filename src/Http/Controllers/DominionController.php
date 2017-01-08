<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use OpenDominion\Models\Dominion;

class DominionController extends AbstractController
{
    public function postPlay(Dominion $dominion)
    {
        // Check if dominion belongs to logged in user
        if ($dominion->user_id != Auth::user()->id) {
            return response('Unauthorized', 401);
        }

        // Check that round is active
        // todo

        session(['selected_dominion_id' => $dominion->id]);
        return redirect(route('dominion.status'));
    }

    public function getStatus()
    {
        return view('pages.dominion.status');
    }
}
