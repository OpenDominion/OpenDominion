<?php

namespace OpenDominion\Http\Controllers;

use OpenDominion\Models\Round;
use OpenDominion\Models\User;

class ValhallaController extends AbstractController
{
    public function getIndex()
    {
        // show list of rounds
    }

    public function getRound(Round $round)
    {
        // show list of types
    }

    public function getRoundType(Round $round, $type)
    {
        // show list of dominions
    }

    public function getUser(User $user)
    {
        // show valhalla of single user
    }

    // todo: search user
}
