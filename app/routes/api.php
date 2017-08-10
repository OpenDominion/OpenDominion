<?php

use Illuminate\Routing\Router;

/** @var Router $router */
$router->group(['prefix' => 'v1'], function (Router $router) {

    $router->get('pbbg', function () {
        return [
            'name' => 'OpenDominion',
            'born' => '2013-02-04',
            'registered_players' => \OpenDominion\Models\User::count(),
            'tags' => ['multiplayer', 'strategy', 'fantasy']
        ];
    });

});
