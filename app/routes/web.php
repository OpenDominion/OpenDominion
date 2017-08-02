<?php

use Illuminate\Routing\Router;

/** @var Router $router */
$router->get('/')->uses('HomeController@getIndex')->name('home');

//$router->get('/test', function () {
//    $networthCalculator = app(\OpenDominion\Contracts\Calculators\NetworthCalculator::class);
//    $realmFactory = app(\OpenDominion\Factories\RealmFactory::class);
//    $realmFinderService = app(\OpenDominion\Contracts\Services\RealmFinderService::class);
//
//    $round = \OpenDominion\Models\Round::find(2);
//
//    $races = [
//        'good' => \OpenDominion\Models\Race::whereAlignment('good')->first(),
//        'evil' => \OpenDominion\Models\Race::whereAlignment('evil')->first(),
//    ];
//
//    for ($i = 0; $i < 500; $i++) {
//        $raceAlignment = (($i % 2 === 0) ? 'good' : 'evil');
//
//        $race = $races[$raceAlignment];
//
//        $user = factory(\OpenDominion\Models\User::class)->create();
//        $realm = $realmFinderService->findRandomRealm($round, $race);
//        if (!$realm) {
//            $realm = $realmFactory->create($round, $raceAlignment);
//        }
//
//        $dominion = factory(\OpenDominion\Models\Dominion::class)->make([
//            'user_id' => $user->id,
//            'round_id' => $round->id,
//            'realm_id' => $realm->id,
//            'race_id' => $race->id,
//        ]);
//
//        $dominion->networth = $networthCalculator->getDominionNetworth($dominion);
//
//        $dominion->save();
//    }
//});

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

    // Dashboard
    $router->get('dashboard')->uses('DashboardController@getIndex')->name('dashboard');

    // Round Register
    $router->get('round/{round}/register')->uses('RoundController@getRegister')->name('round.register');
    $router->post('round/{round}/register')->uses('RoundController@postRegister');

    $router->group(['prefix' => 'dominion', 'as' => 'dominion.'], function (Router $router) {

        // Dominion Select
//        $router->get('{dominion}/select')->uses(function () { return redirect()->route('dashboard'); });
        $router->post('{dominion}/select')->uses('Dominion\SelectController@postSelect')->name('select');

        // Dominion
        $router->group(['middleware' => 'dominionselected'], function (Router $router) {

            // Status
            $router->get('status')->uses('Dominion\StatusController@getStatus')->name('status');

            // Advisors
            $router->get('advisors')->uses('Dominion\AdvisorsController@getAdvisors')->name('advisors');
            $router->get('advisors/production')->uses('Dominion\AdvisorsController@getAdvisorsProduction')->name('advisors.production');
            $router->get('advisors/military')->uses('Dominion\AdvisorsController@getAdvisorsMilitary')->name('advisors.military');
            $router->get('advisors/land')->uses('Dominion\AdvisorsController@getAdvisorsLand')->name('advisors.land');
            $router->get('advisors/construction')->uses('Dominion\AdvisorsController@getAdvisorsConstruction')->name('advisors.construction');
            // todo: magic advisor
            // todo: statistics advisor
            // todo: growth advisor

            // Exploration
            $router->get('explore')->uses('Dominion\ExplorationController@getExplore')->name('explore');
            $router->post('explore')->uses('Dominion\ExplorationController@postExplore');

            // Construction
            $router->get('construction')->uses('Dominion\ConstructionController@getConstruction')->name('construction');
            $router->post('construction')->uses('Dominion\ConstructionController@postConstruction');
            $router->get('destroy')->uses('Dominion\ConstructionController@getDestroy')->name('destroy');
            $router->post('destroy')->uses('Dominion\ConstructionController@postDestroy');

            // Rezoning
            $router->get('rezone')->uses('Dominion\RezoneController@getRezone')->name('rezone');
            $router->post('rezone')->uses('Dominion\RezoneController@postRezone');

            // Military
            $router->get('military')->uses('Dominion\MilitaryController@getMilitary')->name('military');
            $router->post('military/change-draft-rate')->uses('Dominion\MilitaryController@postChangeDraftRate')->name('military.change-draft-rate');
            $router->post('military/train')->uses('Dominion\MilitaryController@postTrain')->name('military.train');

            // Realm
            $router->get('realm/{realm?}')->uses('Dominion\RealmController@getRealm')->name('realm');
            $router->post('realm/change-realm')->uses('Dominion\RealmController@postChangeRealm')->name('realm.change-realm');

            // todo: post/change realm?

            $router->get('debug')->uses('DebugController@getIndex');

        });

    });

});
