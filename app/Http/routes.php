<?php

/** @var \Illuminate\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

$router->get('/', function () {

//    $perkType = \OpenDominion\Models\RacePerkType::with('races.perks')->where('key', 'food_production')->firstOrFail();
//    return $perkType;

//    $race = \OpenDominion\Models\Race::with('perks.type')->firstOrFail();
//    return $race;

//    $league = \OpenDominion\Models\RoundLeague::with('rounds')->firstOrFail();
//    return $league;

    $round = \OpenDominion\Models\Round::with('league', 'realms')->firstOrFail();
    return $round;

//    return view('welcome');

});
