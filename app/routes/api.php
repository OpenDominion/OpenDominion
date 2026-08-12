<?php

use Illuminate\Routing\Router;

/** @var Router $router */
$router->group(['prefix' => 'v1', 'as' => 'api.'], static function (Router $router) {

    $router->group(['middleware' => ['bindings', 'throttle:60,1']], static function (Router $router) {
        $router->get('pbbg')->uses('ApiController@getPbbg');
        $router->post('bugsnag')->uses('ApiController@postBugsnag');
        $router->get('time', static function () {
            return response()
                ->json(['t' => now()->format('H:i:s')])
                ->header('Cache-Control', 'no-store');
        });
    });

    $router->group(['prefix' => 'dominion', 'middleware' => ['api', 'auth', 'dominionselected'], 'as' => 'dominion.'], static function (Router $router) {
        $router->get('invasion')->uses('Dominion\APIController@calculateInvasion')->name('invasion');
    });

    $router->group(['prefix' => 'calculator', 'middleware' => ['api', 'auth'], 'as' => 'calculator.'], static function (Router $router) {
        $router->get('defense')->uses('Dominion\APIController@calculateDefense')->name('defense');
        $router->get('offense')->uses('Dominion\APIController@calculateOffense')->name('offense');
    });

    $router->group(['prefix' => 'user', 'middleware' => ['api', 'auth'], 'as' => 'user.'], static function (Router $router) {
        $router->get('feedback')->uses('Dominion\APIController@endorsePlayer')->name('feedback');
    });

    // Read-only public API: rounds, per-round dominion snapshots, town crier events.
    $router->group(['prefix' => 'rounds', 'middleware' => ['bindings', 'throttle:60,1'], 'as' => 'rounds.'], static function (Router $router) {
        $router->get('/')->uses('Api\V1\RoundController@index')->name('index');
        $router->get('{round}/dominions')->uses('Api\V1\RoundController@dominions')->name('dominions');
        $router->get('{round}/events')->uses('Api\V1\RoundController@events')->name('events');
    });

    // Read-only authenticated API: per-dominion op center via X-API-Key header.
    $router->group(['prefix' => 'dominions', 'middleware' => ['bindings', 'throttle:120,1', 'apikey'], 'as' => 'dominions.'], static function (Router $router) {
        $router->get('me')->uses('Api\V1\OpCenterController@me')->name('me');
        $router->get('me/ops')->uses('Api\V1\OpCenterController@ops')->name('ops');
        $router->get('me/ops/{target}')->uses('Api\V1\OpCenterController@opsForTarget')->name('ops.target');
    });

});
