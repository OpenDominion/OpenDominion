<?php

namespace OpenDominion\Http\Controllers;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use OpenDominion\Models\User;

class AuthController extends BaseController
{
    use AuthenticatesAndRegistersUsers, ThrottlesLogins;

    protected $redirectTo = '/status';

    protected $loginView = 'pages.auth.login';
    protected $registerView = 'pages.auth.register';

    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     * @return Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            // todo
        ]);
    }

    /**
     * Creates a new user instance after a valid registration.
     *
     * @param array $data
     * @return User
     */
    protected function create(array $data)
    {
        return User::create([
            // todo
        ]);
    }
}
