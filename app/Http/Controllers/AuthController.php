<?php

namespace OpenDominion\Http\Controllers;

use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;

class AuthController extends BaseController
{
    use AuthenticatesAndRegistersUsers, ThrottlesLogins;

    protected $loginView = 'pages.auth.login';
    protected $registerView = 'pages.auth.register';
}
