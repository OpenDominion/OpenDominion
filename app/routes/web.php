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
            $router->get('advisors/construct')->uses('Dominion\AdvisorsController@getAdvisorsConstruction')->name('advisors.construct');
            $router->get('advisors/magic')->uses('Dominion\AdvisorsController@getAdvisorsMagic')->name('advisors.magic');
            $router->get('advisors/rankings/{type?}')->uses('Dominion\AdvisorsController@getAdvisorsRankings')->name('advisors.rankings');
            $router->get('advisors/statistics')->uses('Dominion\AdvisorsController@getAdvisorsStatistics')->name('advisors.statistics');

            // Daily
            $router->get('bonuses')->uses('Dominion\DailyBonusesController@getBonuses')->name('bonuses');
            $router->post('bonuses/platinum')->uses('Dominion\DailyBonusesController@postBonusesPlatinum')->name('bonuses.platinum');
            $router->post('bonuses/land')->uses('Dominion\DailyBonusesController@postBonusesLand')->name('bonuses.land');

            // Exploration
            $router->get('explore')->uses('Dominion\ExplorationController@getExplore')->name('explore');
            $router->post('explore')->uses('Dominion\ExplorationController@postExplore');

            // Construction
            $router->get('construct')->uses('Dominion\ConstructionController@getConstruction')->name('construct');
            $router->post('construct')->uses('Dominion\ConstructionController@postConstruction');
            $router->get('destroy')->uses('Dominion\ConstructionController@getDestroy')->name('destroy');
            $router->post('destroy')->uses('Dominion\ConstructionController@postDestroy');

            // Rezoning
            $router->get('rezone')->uses('Dominion\RezoneController@getRezone')->name('rezone');
            $router->post('rezone')->uses('Dominion\RezoneController@postRezone');

            // Improvements
            $router->get('improvements')->uses('Dominion\ImprovementController@getImprovements')->name('improvements');
            $router->post('improvements')->uses('Dominion\ImprovementController@postImprovements');

            // National Bank
            $router->get('bank')->uses('Dominion\BankController@getBank')->name('bank');
            $router->post('bank')->uses('Dominion\BankController@postBank');

            // Military
            $router->get('military')->uses('Dominion\MilitaryController@getMilitary')->name('military');
            $router->post('military/change-draft-rate')->uses('Dominion\MilitaryController@postChangeDraftRate')->name('military.change-draft-rate');
            $router->post('military/train')->uses('Dominion\MilitaryController@postTrain')->name('military.train');
            $router->get('military/release')->uses('Dominion\MilitaryController@getRelease')->name('military.release');
            $router->post('military/release')->uses('Dominion\MilitaryController@postRelease');

            // Magic
            $router->get('magic')->uses('Dominion\MagicController@getMagic')->name('magic');
            $router->post('magic')->uses('Dominion\MagicController@postMagic');

            // Council
            $router->get('council')->uses('Dominion\CouncilController@getIndex')->name('council');
            $router->get('council/create')->uses('Dominion\CouncilController@getCreate')->name('council.create');
            $router->post('council/create')->uses('Dominion\CouncilController@postCreate');
            $router->get('council/{thread}')->uses('Dominion\CouncilController@getThread')->name('council.thread');
            $router->post('council/{thread}/reply')->uses('Dominion\CouncilController@postReply')->name('council.reply');

            // Realm
            $router->get('realm/{realmNumber?}')->uses('Dominion\RealmController@getRealm')->name('realm');
            $router->post('realm/change-realm')->uses('Dominion\RealmController@postChangeRealm')->name('realm.change-realm');

            // Debug
            $router->get('debug')->uses('DebugController@getIndex');
            $router->get('debug/dump')->uses('DebugController@getDump');

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

// Staff

$router->group(['middleware' => ['auth', 'role:Developer|Administrator|Moderator'], 'prefix' => 'staff', 'as' => 'staff.'], function (Router $router) {

    $router->get('/')->uses('Staff\StaffController@getIndex')->name('index');

    // Developer

    $router->group(['middleware' => 'role:Developer', 'prefix' => 'developer', 'as' => 'developer.'], function (Router $router) {

        $router->get('/')->uses('Staff\DeveloperController@getIndex')->name('index');

        // simulate dominion by state string
        // take over dominion & traverse state history
        // set dominion state/attributes?

    });

    // Administrator

    $router->group(['middleware' => 'role:Administrator', 'prefix' => 'administrator', 'as' => 'administrator.'], function (Router $router) {

        $router->resource('dominions', 'Staff\Administrator\DominionController');
        $router->resource('users', 'Staff\Administrator\UserController');

        // view all users
        // view all council boards

    });

    // Moderator

    // todo
    // view flagged posts

});

// Misc
