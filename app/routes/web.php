<?php

use Illuminate\Routing\Router;

/** @var Router $router */
$router->get('/')->uses('HomeController@getIndex')->name('home');

$router->get('/test', function () {
//    $user = \OpenDominion\Models\User::first();
//    event(new \OpenDominion\Events\UserRegisteredEvent($user));

//    $analyticsService = app(\OpenDominion\Contracts\Services\Analytics\AnalyticsService::class);
//    return $analyticsService->getFlashEvents();

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
});

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

    // Profile
    // todo

    // Dashboard
    $router->get('dashboard')->uses('DashboardController@getIndex')->name('dashboard');

    // Settings
    $router->group(['prefix' => 'settings', 'as' => 'settings'], function (Router $router) {

        $router->get('/')->uses('SettingsController@getIndex');

        $router->get('account')->uses('SettingsController@getAccount')->name('.account');
        $router->get('notifications')->uses('SettingsController@getNotifications')->name('.notifications');
        $router->get('security')->uses('SettingsController@getSecurity')->name('.security');

    });

    // Round Register
    $router->get('round/{round}/register')->uses('RoundController@getRegister')->name('round.register');
    $router->post('round/{round}/register')->uses('RoundController@postRegister');

    $router->group(['prefix' => 'dominion', 'as' => 'dominion.'], function (Router $router) {

        // Dominion Select
//        $router->get('{dominion}/select')->uses(function () { return redirect()->route('dashboard'); });
        $router->post('{dominion}/select')->uses('Dominion\SelectController@postSelect')->name('select');

        // Dominion
        $router->group(['middleware' => 'dominionselected'], function (Router $router) {

            $router->get('/', function () {
                return redirect()->route('dominion.status');
            });

            // Status
            $router->get('status')->uses('Dominion\StatusController@getStatus')->name('status');

            // Advisors
            $router->get('advisors')->uses('Dominion\AdvisorsController@getAdvisors')->name('advisors');
            $router->get('advisors/production')->uses('Dominion\AdvisorsController@getAdvisorsProduction')->name('advisors.production');
            $router->get('advisors/military')->uses('Dominion\AdvisorsController@getAdvisorsMilitary')->name('advisors.military');
            $router->get('advisors/land')->uses('Dominion\AdvisorsController@getAdvisorsLand')->name('advisors.land');
            $router->get('advisors/construction')->uses('Dominion\AdvisorsController@getAdvisorsConstruction')->name('advisors.construction');
            $router->get('advisors/magic')->uses('Dominion\AdvisorsController@getAdvisorsMagic')->name('advisors.magic');
            $router->get('advisors/rankings')->uses('Dominion\AdvisorsController@getAdvisorsRankings')->name('advisors.rankings');
            $router->get('advisors/statistics')->uses('Dominion\AdvisorsController@getAdvisorsStatistics')->name('advisors.statistics');

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

            // National Bank
            $router->get('bank')->uses('Dominion\BankController@getBank')->name('bank');
            $router->post('bank')->uses('Dominion\BankController@postBank');

            // Military
            $router->get('military')->uses('Dominion\MilitaryController@getMilitary')->name('military');
            $router->post('military/change-draft-rate')->uses('Dominion\MilitaryController@postChangeDraftRate')->name('military.change-draft-rate');
            $router->post('military/train')->uses('Dominion\MilitaryController@postTrain')->name('military.train');
            $router->get('military/release')->uses('Dominion\MilitaryController@getRelease')->name('military.release');
            $router->post('military/release')->uses('Dominion\MilitaryController@postRelease');

            // Council
            $router->get('council')->uses('Dominion\CouncilController@getIndex')->name('council');
            $router->get('council/create')->uses('Dominion\CouncilController@getCreate')->name('council.create');
            $router->post('council/create')->uses('Dominion\CouncilController@postCreate');
            $router->get('council/{thread}')->uses('Dominion\CouncilController@getThread')->name('council.thread');
            $router->post('council/{thread}/reply')->uses('Dominion\CouncilController@postReply')->name('council.reply');

            // Realm
            $router->get('realm/{realm?}')->uses('Dominion\RealmController@getRealm')->name('realm');
            $router->post('realm/change-realm')->uses('Dominion\RealmController@postChangeRealm')->name('realm.change-realm');

            $router->get('debug')->uses('DebugController@getIndex');

        });

    });

});

// Scribes

// Valhalla

$router->group(['prefix' => 'valhalla', 'as' => 'valhalla.'], function (Router $router) {

    $router->get('/')->uses('ValhallaController@getIndex')->name('index');
    $router->get('round/{round}')->uses('ValhallaController@getRound')->name('round');
    $router->get('round/{round}/{type}')->uses('ValhallaController@getRoundType')->name('round.type');
    $router->get('user/{user}')->uses('ValhallaController@getUser')->name('user');

});

// Donate

// Contact

// Links
