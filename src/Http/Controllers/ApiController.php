<?php

namespace OpenDominion\Http\Controllers;

use Cache;
use Illuminate\Routing\Controller;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\User;

class ApiController extends Controller
{
    public function getPbbg()
    {
        return [
            'name' => 'OpenDominion',
            'version' => (Cache::get('version') ?? 'unknown'),
            'description' => 'A text-based, persistent browser-based strategy game (PBBG) in a fantasy war setting',
            'tags' => ['fantasy', 'multiplayer', 'strategy'],
            'status' => 'up',
            'dates' => [
                'born' => '2013-02-04',
                'updated' => (Cache::has('version-date') ? carbon(Cache::get('version-date'))->format('Y-m-d') : null),
            ],
            'players' => [
                'registered' => User::whereActivated(true)->count(),
                'active' => Dominion::whereHas('round', static function ($q) {
                    $q->where('start_date', '<=', now())
                        ->where('end_date', '>', now());
                })->count(),
            ],
            'links' => [
                'beta' => 'https://beta.opendominion.net',
                'github' => 'https://github.com/OpenDominion/OpenDominion',
            ],
        ];
    }
}
