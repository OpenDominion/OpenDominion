<?php

namespace OpenDominion\Http\Controllers\Auth;

use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use OpenDominion\Http\Controllers\AbstractController;

class LoginController extends AbstractController
{
    use AuthenticatesUsers;

    protected $redirectTo = '/dashboard';

    public function getLogin()
    {
        return view('pages.auth.login');
    }

    public function postLogin(Request $request)
    {
        return $this->login($request);
    }

    public function postLogout(Request $request)
    {
        $response = $this->logout($request);

        session()->flash('alert-success', 'You have been logged out.');

        return $response;
    }
}
