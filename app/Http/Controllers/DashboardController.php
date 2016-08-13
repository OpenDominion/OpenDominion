<?php

namespace OpenDominion\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use OpenDominion\Models\Round;

class DashboardController extends AbstractController
{
    public function getIndex()
    {
        $usersDominions = new Collection(); // todo
        $rounds = Round::with('league')->where('end_date', '>', new Carbon('today'))->get();

        return view('pages.dashboard', [
            'dominions' => $usersDominions,
            'rounds' => $rounds,
        ]);
    }
}
