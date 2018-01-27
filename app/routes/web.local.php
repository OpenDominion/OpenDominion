<?php

use Illuminate\Routing\Router;

/** @var Router $router */
$router->get('/test', function () {
    if (auth()->guest()) {
        redirect()->route('auth.login');
    }

    $user = auth()->user();
    $dominion = $user->dominions->first();

    // ...
});
