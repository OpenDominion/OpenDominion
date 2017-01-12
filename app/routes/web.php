<?php

use Illuminate\Routing\Router;

/** @var Router $router */

$router->get('/', ['as' => 'home', function () {
    if (Auth::check()) {
        return redirect(route('dashboard'));
    }

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

        $router->post('{dominion}/select', ['as' => 'dominion.select', 'uses' => 'DominionController@postSelect']);

        $router->group(['middleware' => 'dominionselected'], function (Router $router) {

            // Dominion

            $router->get('status', ['as' => 'dominion.status', 'uses' => 'DominionController@getStatus']);
            $router->get('advisors', ['as' => 'dominion.advisors', 'uses' => 'DominionController@getAdvisors']);
            $router->get('advisors/production', ['as' => 'dominion.advisors.production', 'uses' => 'DominionController@getAdvisorsProduction']);
            $router->get('advisors/military', ['as' => 'dominion.advisors.military', 'uses' => 'DominionController@getAdvisorsMilitary']);
            $router->get('advisors/land', ['as' => 'dominion.advisors.land', 'uses' => 'DominionController@getAdvisorsLand']);
            $router->get('advisors/construction', ['as' => 'dominion.advisors.construction', 'uses' => 'DominionController@getAdvisorsConstruction']);

            // Actions

            $router->get('explore', ['as' => 'dominion.explore', 'uses' => 'DominionController@getExplore']);
            $router->get('construction', ['as' => 'dominion.construction', 'uses' => 'DominionController@getConstruction']);
//            $router->get('rezone-land', ['as' => 'dominion.rezone-land', 'uses' => 'DominionController@getRezoneLand']);
//            $router->get('improvements', ['as' => 'dominion.improvements', 'uses' => 'DominionController@getImprovements']);
//            $router->get('national-bank', ['as' => 'dominion.national-bank', 'uses' => 'DominionController@getNationalBank']);

            // Black Ops

            // Comms?

            // Realm

            // Misc?

            // Debug

            $router->get('debug', function () {
                if (app()->environment() === 'production') {
                    return redirect(route('dominion.status'));
                }

                $dominionSelectorService = app()->make(\OpenDominion\Services\DominionSelectorService::class);
                $dominion = $dominionSelectorService->getUserSelectedDominion();

                $populationCalculator = app()->make(\OpenDominion\Calculators\Dominion\PopulationCalculator::class, [$dominion]);
                $productionCalculator = app()->make(\OpenDominion\Calculators\Dominion\ProductionCalculator::class, [$dominion]);

                function printCalculatorMethodValues($calculator, array $methods) {
                    $return = '';

                    foreach ($methods as $method) {
                        $label = implode(' ', preg_split('/(?=[A-Z])/', ltrim($method, 'get')));
                        $value = $calculator->$method();
                        $type = gettype($value);

                        $return .= ($label . ' :');

                        if (is_scalar($value)) {
                            if (is_integer($value)) {
                                $value = number_format($value);
                            } elseif (is_float($value) || is_double($value)) {

                                if (substr($label, -10) === 'Multiplier') {
                                    $value = number_format($value * 100 - 100, 2);
                                    $value = ((($value < 0) ? '-' : '+') . $value . '%');
                                } else {
                                    $value = number_format($value, 2);
                                }

                                if (substr($label, -10) === 'Percentage') {
                                    $value .= '%';
                                }
                            }

                            $return .= (' <b>' . $value . '</b> (' . $type . ')');

                        } elseif (is_array($value)) {
                            $return .= ('<pre>' . print_r($value, true) . '</pre>');
                        }

                        $return .= '<br>';
                    }

                    return $return;
                }

                return view('pages.dominion.debug', compact(
                    'populationCalculator',
                    'productionCalculator'
                ));
            });

        });

    });

});
