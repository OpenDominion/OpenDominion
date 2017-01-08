<?php

use Illuminate\Routing\Router;

/** @var Router $router */

$router->get('/', ['as' => 'home', function () {
    return view('pages.home');
}]);

// Authentication

$router->group(['prefix' => 'auth'], function (Router $router) {

    $router->group(['middleware' => 'guest'], function (Router $router) {

        $router->get('login', ['as' => 'auth.login', 'uses' => 'Auth\LoginController@getLogin']);
        $router->post('login', 'Auth\LoginController@postLogin');

        $router->get('register', ['as' => 'auth.register', 'uses' => 'Auth\RegisterController@getRegister']);
        $router->post('register', 'Auth\RegisterController@postRegister');

        $router->get('activate/{activation_code}', ['as' => 'auth.activate', 'uses' => 'Auth\RegisterController@getActivate']);

    });

    $router->group(['middleware' => 'auth'], function (Router $router) {

        $router->post('logout', ['as' => 'auth.logout', 'uses' => 'Auth\LoginController@postLogout']);

    });

});

// Gameplay

$router->group(['middleware' => 'auth'], function (Router $router) {

    $router->get('dashboard', ['as' => 'dashboard', 'uses' => 'DashboardController@getIndex']);

    $router->get('round/{round}/register', ['as' => 'round.register', 'uses' => 'RoundController@getRegister']);
    $router->post('round/{round}/register', 'RoundController@postRegister');

    $router->group(['prefix' => 'dominion'], function (Router $router) {

        $router->post('{dominion}/play', ['as' => 'dominion.play', 'uses' => 'DominionController@postPlay']);

        $router->group(['middleware' => 'dominionselected'], function (Router $router) {

            $router->get('status', ['as' => 'dominion.status', 'uses' => 'DominionController@getStatus']);

        });

    });

});
