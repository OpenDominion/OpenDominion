<?php

use Illuminate\Routing\Router;
use Spatie\Honeypot\ProtectAgainstSpam;

/** @var Router $router */
$router->get('/')->uses('HomeController@getIndex')->name('home');
$router->get('user-agreement')->uses('HomeController@getUserAgreement')->name('user-agreement');

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

    // Message Board
    $router->get('message-board')->uses('MessageBoardController@getIndex')->name('message-board');
    $router->get('message-board/create')->uses('MessageBoardController@getCreate')->name('message-board.create');
    $router->post('message-board/create')->uses('MessageBoardController@postCreate');
    $router->get('message-board/{thread}')->uses('MessageBoardController@getThread')->name('message-board.thread');
    $router->post('message-board/{thread}/reply')->uses('MessageBoardController@postReply')->name('message-board.reply');
    $router->get('message-board/{thread}/delete')->uses('MessageBoardController@getDeleteThread')->name('message-board.delete.thread');
    $router->post('message-board/{thread}/delete')->uses('MessageBoardController@postDeleteThread');
    $router->get('message-board/{thread}/flag')->uses('MessageBoardController@getFlagThread')->name('message-board.flag.thread');
    $router->get('message-board/post/{post}/delete')->uses('MessageBoardController@getDeletePost')->name('message-board.delete.post');
    $router->post('message-board/post/{post}/delete')->uses('MessageBoardController@postDeletePost');
    $router->get('message-board/post/{post}/flag')->uses('MessageBoardController@getFlagPost')->name('message-board.flag.post');

    $router->group(['prefix' => 'dominion', 'as' => 'dominion.'], static function (Router $router) {
        
        // Dominion Select
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
            $router->get('advisors/castle')->uses('Dominion\AdvisorsController@getAdvisorsCastle')->name('advisors.castle');
            $router->get('advisors/magic')->uses('Dominion\AdvisorsController@getAdvisorsMagic')->name('advisors.magic');
            $router->get('advisors/rankings')->uses('Dominion\AdvisorsController@getAdvisorsRankings')->name('advisors.rankings');
            $router->get('advisors/statistics')->uses('Dominion\AdvisorsController@getAdvisorsStatistics')->name('advisors.statistics');

            $router->get('realm/advisors/{target}/status')->uses('Dominion\AdvisorsController@getAdvisorsStatus')->name('realm.advisors.status');
            $router->get('realm/advisors/{target}/production')->uses('Dominion\AdvisorsController@getAdvisorsProduction')->name('realm.advisors.production');
            $router->get('realm/advisors/{target}/military')->uses('Dominion\AdvisorsController@getAdvisorsMilitary')->name('realm.advisors.military');
            $router->get('realm/advisors/{target}/land')->uses('Dominion\AdvisorsController@getAdvisorsLand')->name('realm.advisors.land');
            $router->get('realm/advisors/{target}/construct')->uses('Dominion\AdvisorsController@getAdvisorsConstruction')->name('realm.advisors.construct');
            $router->get('realm/advisors/{target}/castle')->uses('Dominion\AdvisorsController@getAdvisorsCastle')->name('realm.advisors.castle');
            $router->get('realm/advisors/{target}/magic')->uses('Dominion\AdvisorsController@getAdvisorsMagic')->name('realm.advisors.magic');
            $router->get('realm/advisors/{target}/rankings')->uses('Dominion\AdvisorsController@getAdvisorsRankings')->name('realm.advisors.rankings');
            $router->get('realm/advisors/{target}/statistics')->uses('Dominion\AdvisorsController@getAdvisorsStatistics')->name('realm.advisors.statistics');

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
            $router->post('improvements/resource')->uses('Dominion\ImprovementController@postPreferredResource')->name('improvements.resource');

            // National Bank
            $router->get('bank')->uses('Dominion\BankController@getBank')->name('bank');
            $router->post('bank')->uses('Dominion\BankController@postBank');

            // Techs
            $router->get('techs')->uses('Dominion\TechController@getTechs')->name('techs');
            $router->post('techs')->uses('Dominion\TechController@postTechs');

            // Wonders
            $router->get('wonders')->uses('Dominion\WonderController@getWonders')->name('wonders');
            $router->post('wonders')->uses('Dominion\WonderController@postWonders');

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

            // Calculations
            $router->get('calculations')->uses('Dominion\CalculationsController@getIndex')->name('calculations');

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

            // Forum
            $router->get('forum')->uses('Dominion\ForumController@getIndex')->name('forum');
            $router->get('forum/create')->uses('Dominion\ForumController@getCreate')->name('forum.create');
            $router->post('forum/create')->uses('Dominion\ForumController@postCreate');
            $router->get('forum/{thread}')->uses('Dominion\ForumController@getThread')->name('forum.thread');
            $router->post('forum/{thread}/reply')->uses('Dominion\ForumController@postReply')->name('forum.reply');
            $router->get('forum/{thread}/delete')->uses('Dominion\ForumController@getDeleteThread')->name('forum.delete.thread');
            $router->post('forum/{thread}/delete')->uses('Dominion\ForumController@postDeleteThread');
            $router->get('forum/{thread}/flag')->uses('Dominion\ForumController@getFlagThread')->name('forum.flag.thread');
            $router->get('forum/post/{post}/delete')->uses('Dominion\ForumController@getDeletePost')->name('forum.delete.post');
            $router->post('forum/post/{post}/delete')->uses('Dominion\ForumController@postDeletePost');
            $router->get('forum/post/{post}/flag')->uses('Dominion\ForumController@getFlagPost')->name('forum.flag.post');

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
            $router->post('government/war/declare')->uses('Dominion\GovernmentController@postDeclareWar')->name('government.war.declare');
            $router->post('government/war/cancel')->uses('Dominion\GovernmentController@postCancelWar')->name('government.war.cancel');
            $router->post('government/advisors')->uses('Dominion\GovernmentController@postAdvisors')->name('government.advisors');

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
            $router->post('misc/report')->uses('Dominion\MiscController@postReport')->name('misc.report');
            $router->get('misc/restart')->uses('Dominion\MiscController@getRestartDominion')->name('misc.restart');
            $router->post('misc/restart')->uses('Dominion\MiscController@postRestartDominion');
            $router->get('misc/tick')->uses('Dominion\MiscController@getTickDominion')->name('misc.tick');

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
    $router->get('tech')->uses('ScribesController@getTechs')->name('techs');
    $router->get('wonders')->uses('ScribesController@getWonders')->name('wonders');
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
    $router->get('/audit')->uses('Staff\StaffController@getAudit')->name('audit');

    // Administrator

    $router->group(['middleware' => 'role:Administrator', 'prefix' => 'administrator', 'as' => 'administrator.'], static function (Router $router) {

        $router->resource('dominions', 'Staff\Administrator\DominionController');

        $router->get('users/{user}/take-over', 'Staff\Administrator\UserController@takeOver')->name('users.take-over');
        $router->resource('users', 'Staff\Administrator\UserController');

        // view all users
        // view all council boards

    });

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

    // Moderator
    $router->group(['middleware' => 'role:Administrator|Moderator', 'prefix' => 'moderator', 'as' => 'moderator.'], static function (Router $router) {

        $router->get('dominions/{dominion}/event/{gameEvent}', 'Staff\Moderator\DominionController@showGameEvent')->name('dominion.event');
        $router->get('dominions/{dominion}/activity', 'Staff\Moderator\DominionController@showUserActivity')->name('dominion.activity');
        $router->post('dominions/{dominion}/lock', 'Staff\Moderator\DominionController@lockDominion')->name('dominion.lock');
        $router->post('dominions/{dominion}/unlock', 'Staff\Moderator\DominionController@unlockDominion')->name('dominion.unlock');
        $router->resource('dominions', 'Staff\Moderator\DominionController');
    });

    // todo
    // view flagged posts

});

// Misc
