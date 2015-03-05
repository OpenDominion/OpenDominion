<?php namespace OpenDominion\Http\Controllers;

use Illuminate\Contracts\Auth\Guard;
use OpenDominion\Commands\User\LoginCommand;
use OpenDominion\Http\Requests\Auth\LoginRequest;
use OpenDominion\Http\Requests\Auth\RegisterRequest;
use OpenDominion\Models\User;

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
        $this->dispatch(new LoginCommand(
            $request->get('email'),
            $request->get('password'),
            $request->has('remember')
        ));

        return redirect('/status');
    }

    public function getRegister()
    {
        return view('auth.register');
    }

    public function postRegister(RegisterRequest $request)
    {

        // Check email
        $email = $request->get('email');
        $password = $request->get('password');

        $user = User::where('email', $email)->first();

        if ($user !== null) {
            return 'Email already exists';
        }

        return 'Success!';
    }

    public function getLogout()
    {
        $this->auth->logout();

        return redirect('/');
    }
}
