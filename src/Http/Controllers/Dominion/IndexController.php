<?php

namespace OpenDominion\Http\Controllers\Dominion;

class IndexController extends AbstractDominionController
{
    public function getIndex()
    {
        // This class is here else the routes file will fail to cache with a
        // closure
        return redirect()
            ->route('dominion.status');
    }
}
