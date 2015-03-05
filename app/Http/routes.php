<?php

$router->get('/', 'HomeController@getIndex');

$router->controller('auth', 'AuthController');

$router->group(['middleware' => 'auth'], function () use ($router) {

    $router->get('/status', 'StatusController@getIndex');

});


