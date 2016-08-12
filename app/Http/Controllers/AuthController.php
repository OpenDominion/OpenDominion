<?php

namespace OpenDominion\Http\Controllers;

use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use OpenDominion\Models\User;
use Validator;

class AuthController extends BaseController
{
    use AuthenticatesAndRegistersUsers, ThrottlesLogins;

    protected $redirectTo = '/dashboard';

    protected $loginView = 'pages.auth.login';
    protected $registerView = 'pages.auth.register';

    public function getLogout()
    {
        request()->session()->flash('alert-success', 'You have been logged out.');

        return $this->logout();
    }

    /**
     * {@inheritDoc}
     */
    public function register(Request $request)
    {
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            $this->throwValidationException(
                $request, $validator
            );
        }

        $this->create($request->all());

        // todo: send activation mail

        $request->session()->flash('alert-success', 'You have been successfully registered. An activation email has been dispatched to your address.');

        return redirect(route('home'));
    }

    // todo: /auth/activate/$id/$activation_code

    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     * @return Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'display_name' => 'required|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
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
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'display_name' => $data['display_name'],
            'activation_code' => str_random(),
        ]);
    }
}
