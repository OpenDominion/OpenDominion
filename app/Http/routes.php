<?php

$router->get('/', 'HomeController@getIndex');

$router->controller('auth', 'AuthController');

$router->get('/status', 'StatusController@getIndex');
