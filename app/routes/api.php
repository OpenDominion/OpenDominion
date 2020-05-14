<?php

use Illuminate\Routing\Router;

/** @var Router $router */
$router->group(['prefix' => 'v1', 'as' => 'api.'], static function (Router $router) {

    $router->group(['middleware' => ['bindings', 'throttle:60,1']], static function (Router $router) {
        $router->get('pbbg')->uses('ApiController@getPbbg');
    });

    $router->group(['prefix' => 'dominion', 'middleware' => ['api', 'auth', 'dominionselected'], 'as' => 'dominion.'], static function (Router $router) {
        $router->get('invasion')->uses('Dominion\APIController@calculateInvasion')->name('invasion');
    });

    $router->group(['prefix' => 'calculator', 'middleware' => ['api', 'auth'], 'as' => 'calculator.'], static function (Router $router) {
        $router->get('defense')->uses('Dominion\APIController@calculateDefense')->name('defense');
        $router->get('offense')->uses('Dominion\APIController@calculateOffense')->name('offense');
    });

});
