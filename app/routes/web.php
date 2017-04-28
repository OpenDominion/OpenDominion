<?php

use Illuminate\Routing\Router;

/** @var Router $router */
$router->get('/')->uses('HomeController@getIndex')->name('home');

// Authentication

$router->group(['prefix' => 'auth', 'as' => 'auth.'], function (Router $router) {

    $router->group(['middleware' => 'guest'], function (Router $router) {

        $router->get('login')->uses('Auth\LoginController@getLogin')->name('login');
        $router->post('login')->uses('Auth\LoginController@postLogin');

        $router->get('register')->uses('Auth\RegisterController@getRegister')->name('register');
        $router->post('register')->uses('Auth\RegisterController@postRegister');

        $router->get('activate/{activation_code}')->uses('Auth\RegisterController@getActivate')->name('activate');

    });

    $router->group(['middleware' => 'auth'], function (Router $router) {

        $router->post('logout')->uses('Auth\LoginController@postLogout')->name('logout');

    });

});

// Gameplay

$router->group(['middleware' => 'auth'], function (Router $router) {

    $router->get('dashboard')->uses('DashboardController@getIndex')->name('dashboard');

    $router->get('round/{round}/register')->uses('RoundController@getRegister')->name('round.register');
    $router->post('round/{round}/register')->uses('RoundController@postRegister');

    $router->group(['prefix' => 'dominion', 'as' => 'dominion.'], function (Router $router) {

//        $router->get('{dominion}/select')->uses(function () { return redirect()->route('dashboard'); });
        $router->post('{dominion}/select')->uses('DominionController@postSelect')->name('select');

        $router->group(['middleware' => 'dominionselected'], function (Router $router) {

            // Other Dominion actions

            $router->get('realm/{realm}')->uses('DominionController@getRealm')->name('other.realm');
//            $router->get('{dominion}/status', ['as' => 'dominion.other.status', 'uses' => 'DominionController@getOtherStatus']);

            // Dominion

            $router->get('status')->uses('DominionController@getStatus')->name('status');
            $router->get('advisors')->uses('DominionController@getAdvisors')->name('advisors');
            $router->get('advisors/production')->uses('DominionController@getAdvisorsProduction')->name('advisors.production');
            $router->get('advisors/military')->uses('DominionController@getAdvisorsMilitary')->name('advisors.military');
            $router->get('advisors/land')->uses('DominionController@getAdvisorsLand')->name('advisors.land');
            $router->get('advisors/construction')->uses('DominionController@getAdvisorsConstruction')->name('advisors.construction');

            // Actions

            $router->get('explore')->uses('DominionController@getExplore')->name('explore');
            $router->post('explore')->uses('DominionController@postExplore');

            $router->get('construction')->uses('DominionController@getConstruction')->name('construction');
            $router->post('construction')->uses('DominionController@postConstruction');
            $router->get('destroy')->uses('DominionController@getDestroy')->name('destroy');
            $router->post('destroy')->uses('DominionController@postDestroy');

//            $router->get('rezone-land', ['as' => 'dominion.rezone-land', 'uses' => 'DominionController@getRezoneLand']);
//            $router->get('improvements', ['as' => 'dominion.improvements', 'uses' => 'DominionController@getImprovements']);
//            $router->get('national-bank', ['as' => 'dominion.national-bank', 'uses' => 'DominionController@getNationalBank']);

            // Black Ops

            // Comms?

            // Realm

            $router->get('realm')->uses('DominionController@getRealm')->name('realm');
            // todo: post/change realm

            // Misc?

            // Debug

            $router->get('debug')->uses('DebugController@getIndex');

        });

    });

});
