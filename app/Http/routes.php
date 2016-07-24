<?php

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Routing\Router;

/** @var Router $router */

// Static pages

$router->get('/', ['as' => 'home', function () {
    return view('pages.home');
}]);

// Authentication

$router->group(['prefix' => 'auth'], function (Router $router) {

    $router->group(['middleware' => 'guest'], function (Router $router) {

        $router->get('login', ['as' => 'auth.login', 'uses' => 'AuthController@getLogin']);
        $router->post('login', 'AuthController@postLogin');

        $router->get('register', ['as' => 'auth.register', 'uses' => 'AuthController@getRegister']);
        $router->post('register', 'AuthController@postRegister');

    });

    $router->group(['middleware' => 'auth'], function (Router $router) {

        $router->get('logout', ['as' => 'auth.logout', 'uses' => 'AuthController@getLogout']);

    });

});

// Gameplay

$router->group(['middleware' => 'auth'], function (Router $router) {

    $router->get('dashboard', ['as' => 'dashboard', function (Guard $auth) {
        return view('pages.dashboard', [
            'dominions' => new \Illuminate\Support\Collection(), // todo: $auth->user()->dominions
            'rounds' => \OpenDominion\Models\Round
                ::with('league')
//                ->where('start_date', '<=', new DateTime('today'))
                ->where('end_date', '>', new DateTime('today'))
                ->get(),
        ]);
    }]);

//    $router->get('status', ['as' => 'status', 'uses' => 'StatusController@getIndex']);
    $router->get('status', ['as' => 'status', function () {
        return 'temp status page';
    }]);

});
