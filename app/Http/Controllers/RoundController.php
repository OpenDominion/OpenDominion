<?php

namespace OpenDominion\Http\Controllers;

use OpenDominion\Http\Requests\AbstractRequest;
use OpenDominion\Models\Round;

class RoundController extends AbstractController
{
    public function getRegister(Round $round)
    {
        return $round;
    }

    public function postRegister(AbstractRequest $request, Round $round)
    {
        // todo
    }
}
