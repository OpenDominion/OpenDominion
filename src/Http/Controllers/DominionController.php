<?php

namespace OpenDominion\Http\Controllers;

use OpenDominion\Models\Dominion;

class DominionController extends AbstractController
{
    //

    public function getStatus(Dominion $dominion)
    {
        return view('pages.dominion.status', [
            'dominion' => $dominion,
        ]);
    }
}
