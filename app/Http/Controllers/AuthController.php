<?php namespace OpenDominion\Http\Controllers;

use Illuminate\Contracts\Auth\Guard;
use OpenDominion\Commands\User\LoginCommand;
use OpenDominion\Commands\User\RegisterCommand;
use OpenDominion\Exceptions\InvalidLoginException;
use OpenDominion\Exceptions\RegistrationException;
use OpenDominion\Http\Requests\Auth\LoginRequest;
use OpenDominion\Http\Requests\Auth\RegisterRequest;

class AuthController extends Controller
{
    protected $auth;

    function __construct(Guard $auth)
    {
        $this->auth = $auth;

        $this->middleware('guest', ['except' => 'getLogout']);
    }

    public function getLogin()
    {
        return view('auth.login');
    }

    public function postLogin(LoginRequest $request)
    {
        try {
            $this->dispatch(new LoginCommand(
                $request->get('email'),
                $request->get('password'),
                $request->has('remember')
            ));

        } catch (InvalidLoginException $e) {
            return redirect('/auth/login')
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return redirect('/status');
    }

    public function getRegister()
    {
        return view('auth.register');
    }

    public function postRegister(RegisterRequest $request)
    {
        $this->dispatch(new RegisterCommand(
            $request->get('email'),
            $request->get('password'),
            $request->get('dominion_name')
        ));

        return view('auth.register-success');
    }

    public function getLogout()
    {
        $this->auth->logout();

        return redirect('/');
    }
}
