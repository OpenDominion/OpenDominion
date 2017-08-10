<?php

use Illuminate\Routing\Router;

/** @var Router $router */
$router->group(['prefix' => 'v1'], function (Router $router) {

    $router->get('pbbg', function () {
        return [
            'name' => 'OpenDominion',
            'born' => '2013-02-04',
            'registered_players' => \OpenDominion\Models\User::count(),
            'tags' => ['fantasy', 'multiplayer', 'strategy'],
        ];
    });

    $router->get('pbbg2', function () {
        return [
            'name' => 'OpenDominion',
            'version' => (Cache::has('version') ? Cache::get('version') : 'unknown'),
            'description' => 'A text-based, persistent browser-based strategy game (PBBG) in a fantasy war setting',
            'tags' => ['fantasy', 'multiplayer', 'strategy'],
            'status' => 'up',
            'dates' => [
                'born' => '2013-02-04',
                'updated' => (Cache::has('version-date') ? (new \Carbon\Carbon(Cache::get('version-date')))->format('Y-m-d') : null),
            ],
            'players' => [
                'registered' => \OpenDominion\Models\User::count(),
                'active' => \OpenDominion\Models\Dominion::whereHas('round', function ($q) {
                    $q->where('start_date', '<=', \Carbon\Carbon::now())
                        ->where('end_date', '>', \Carbon\Carbon::now());
                })->count(),
            ],
            'links' => [
                'beta' => 'https://dev.opendominion.wavehack.net/',
                'github' => 'https://github.com/WaveHack/OpenDominion',
            ],
        ];
    });

});
