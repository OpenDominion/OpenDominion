<?php

use Illuminate\Routing\Router;
use Spatie\Honeypot\ProtectAgainstSpam;

/** @var Router $router */
$router->get('/')->uses('HomeController@getIndex')->name('home');

// Authentication

$router->group(['prefix' => 'auth', 'as' => 'auth.'], static function (Router $router) {

    $router->group(['middleware' => 'guest'], static function (Router $router) {

        // Authentication
        $router->get('login')->uses('Auth\LoginController@showLoginForm')->name('login');
        $router->post('login')->uses('Auth\LoginController@login');

        // Registration
        $router->get('register')->uses('Auth\RegisterController@showRegistrationForm')->name('register');
        $router->post('register')->uses('Auth\RegisterController@register')->middleware(ProtectAgainstSpam::class);
        $router->get('activate/{activation_code}')->uses('Auth\RegisterController@activate')->name('activate');

        // Password Reset
        $router->get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
        $router->post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
        $router->get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
        $router->post('password/reset', 'Auth\ResetPasswordController@reset');

    });

    $router->group(['middleware' => 'auth'], static function (Router $router) {

        // Logout
        $router->post('logout')->uses('Auth\LoginController@logout')->name('logout');

    });

});

// Gameplay

$router->group(['middleware' => 'auth'], static function (Router $router) {

    // Profile
    // todo

    // Dashboard
    $router->get('dashboard')->uses('DashboardController@getIndex')->name('dashboard');

    // Settings
    $router->get('settings')->uses('SettingsController@getIndex')->name('settings');
    $router->post('settings')->uses('SettingsController@postIndex');

    // Round Register
    $router->get('round/{round}/register')->uses('RoundController@getRegister')->name('round.register');
    $router->post('round/{round}/register')->uses('RoundController@postRegister');

    $router->group(['prefix' => 'dominion', 'as' => 'dominion.'], static function (Router $router) {

        // Dominion Select
//        $router->get('{dominion}/select')->uses(function () { return redirect()->route('dashboard'); });
        $router->post('{dominion}/select')->uses('Dominion\SelectController@postSelect')->name('select');

        // Dominion
        $router->group(['middleware' => 'dominionselected'], static function (Router $router) {

            $router->get('/')->uses('Dominion\IndexController@getIndex');

            // Status
            $router->get('status')->uses('Dominion\StatusController@getStatus')->name('status');

            // Advisors
            $router->get('advisors')->uses('Dominion\AdvisorsController@getAdvisors')->name('advisors');
            $router->get('advisors/production')->uses('Dominion\AdvisorsController@getAdvisorsProduction')->name('advisors.production');
            $router->get('advisors/military')->uses('Dominion\AdvisorsController@getAdvisorsMilitary')->name('advisors.military');
            $router->get('advisors/land')->uses('Dominion\AdvisorsController@getAdvisorsLand')->name('advisors.land');
            $router->get('advisors/construct')->uses('Dominion\AdvisorsController@getAdvisorsConstruction')->name('advisors.construct');
            $router->get('advisors/magic')->uses('Dominion\AdvisorsController@getAdvisorsMagic')->name('advisors.magic');
//            $router->get('advisors/rankings')->uses('Dominion\AdvisorsController@getAdvisorsRankings')->name('advisors.rankings');
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

            // Invade
            $router->get('invade')->uses('Dominion\InvasionController@getInvade')->name('invade');
            $router->post('invade')->uses('Dominion\InvasionController@postInvade');

            // Event result
            $router->get('event/{uuid}')->uses('Dominion\EventController@index')->name('event');

            // Magic
            $router->get('magic')->uses('Dominion\MagicController@getMagic')->name('magic');
            $router->post('magic')->uses('Dominion\MagicController@postMagic');

            // Espionage
            $router->get('espionage')->uses('Dominion\EspionageController@getEspionage')->name('espionage');
            $router->post('espionage')->uses('Dominion\EspionageController@postEspionage');

            // Search
            $router->get('search')->uses('Dominion\SearchController@getSearch')->name('search');

            // Council
            $router->get('council')->uses('Dominion\CouncilController@getIndex')->name('council');
            $router->get('council/create')->uses('Dominion\CouncilController@getCreate')->name('council.create');
            $router->post('council/create')->uses('Dominion\CouncilController@postCreate');
            $router->get('council/{thread}')->uses('Dominion\CouncilController@getThread')->name('council.thread');
            $router->post('council/{thread}/reply')->uses('Dominion\CouncilController@postReply')->name('council.reply');
            $router->get('council/{thread}/delete')->uses('Dominion\CouncilController@getDeleteThread')->name('council.delete.thread');
            $router->post('council/{thread}/delete')->uses('Dominion\CouncilController@postDeleteThread');
            $router->get('council/post/{post}/delete')->uses('Dominion\CouncilController@getDeletePost')->name('council.delete.post');
            $router->post('council/post/{post}/delete')->uses('Dominion\CouncilController@postDeletePost');

            // Op Center
            $router->get('op-center')->uses('Dominion\OpCenterController@getIndex')->name('op-center');
            //$router->get('op-center/clairvoyance/{realmNumber}')->uses('Dominion\OpCenterController@getClairvoyance')->name('op-center.clairvoyance');
            $router->get('op-center/{dominion}')->uses('Dominion\OpCenterController@getDominion')->name('op-center.show');
            $router->get('op-center/{dominion}/{type}')->uses('Dominion\OpCenterController@getDominionArchive')->name('op-center.archive');

            // Government
            $router->get('government')->uses('Dominion\GovernmentController@getIndex')->name('government');
            $router->post('government/monarch')->uses('Dominion\GovernmentController@postMonarch')->name('government.monarch');
            $router->post('government/realm')->uses('Dominion\GovernmentController@postRealm')->name('government.realm');
            $router->post('government/royal-guard/join')->uses('Dominion\GovernmentController@postJoinRoyalGuard')->name('government.royal-guard.join');
            $router->post('government/elite-guard/join')->uses('Dominion\GovernmentController@postJoinEliteGuard')->name('government.elite-guard.join');
            $router->post('government/royal-guard/leave')->uses('Dominion\GovernmentController@postLeaveRoyalGuard')->name('government.royal-guard.leave');
            $router->post('government/elite-guard/leave')->uses('Dominion\GovernmentController@postLeaveEliteGuard')->name('government.elite-guard.leave');

            // Rankings
            $router->get('rankings/{type?}')->uses('Dominion\RankingsController@getRankings')->name('rankings');

            // Realm
            $router->get('realm/{realmNumber?}')->uses('Dominion\RealmController@getRealm')->name('realm');
            $router->post('realm/change-realm')->uses('Dominion\RealmController@postChangeRealm')->name('realm.change-realm');

            // Town Crier
            $router->get('town-crier/{realmNumber?}')->uses('Dominion\TownCrierController@getIndex')->name('town-crier');

            // Misc
            $router->post('misc/clear-notifications')->uses('Dominion\MiscController@postClearNotifications')->name('misc.clear-notifications');
            $router->post('misc/close-pack')->uses('Dominion\MiscController@postClosePack')->name('misc.close-pack');

            // Debug
            // todo: remove me later
            $router->get('debug')->uses('DebugController@getIndex');
            $router->get('debug/dump')->uses('DebugController@getDump');

        });

    });

});

// Scribes

$router->group(['prefix' => 'scribes', 'as' => 'scribes.'], static function (Router $router) {
    $router->get('races')->uses('ScribesController@getRaces')->name('races');
    $router->get('construction')->uses('ScribesController@getConstruction')->name('construction');
    $router->get('espionage')->uses('ScribesController@getEspionage')->name('espionage');
    $router->get('magic')->uses('ScribesController@getMagic')->name('magic');
    $router->get('{race}')->uses('ScribesController@getRace')->name('race');
});

// Valhalla

$router->group(['prefix' => 'valhalla', 'as' => 'valhalla.'], static function (Router $router) {

    $router->get('/')->uses('ValhallaController@getIndex')->name('index');
    $router->get('round/{round}')->uses('ValhallaController@getRound')->name('round');
    $router->get('round/{round}/{type}')->uses('ValhallaController@getRoundType')->name('round.type');
    $router->get('user/{user}')->uses('ValhallaController@getUser')->name('user');

});

// Donate

// Contact

// Links

// Staff

$router->group(['middleware' => ['auth', 'role:Developer|Administrator|Moderator'], 'prefix' => 'staff', 'as' => 'staff.'], static function (Router $router) {

    $router->get('/')->uses('Staff\StaffController@getIndex')->name('index');

    // Developer

//    $router->group(['middleware' => 'role:Developer', 'prefix' => 'developer', 'as' => 'developer.'], function (Router $router) {
//
//        $router->get('/')->uses('Staff\DeveloperController@getIndex')->name('index');
//
//        // simulate dominion by state string
//        // take over dominion & traverse state history
//        // set dominion state/attributes?
//
//    });

    // Administrator

    $router->group(['middleware' => 'role:Administrator', 'prefix' => 'administrator', 'as' => 'administrator.'], static function (Router $router) {

        $router->resource('dominions', 'Staff\Administrator\DominionController');

        $router->get('users/{user}/take-over', 'Staff\Administrator\UserController@takeOver')->name('users.take-over');
        $router->resource('users', 'Staff\Administrator\UserController');

        // view all users
        // view all council boards

    });

    // Moderator

    // todo
    // view flagged posts

});

// Misc
