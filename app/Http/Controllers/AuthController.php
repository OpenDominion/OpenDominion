<?php namespace OpenDominion\Http\Controllers;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use OpenDominion\Models\User;

class AuthController extends Controller
{
    protected $auth;

    function __construct(Guard $auth)
    {
        $this->auth = $auth;

//        $this->middleware('guest');
        $this->middleware('guest', ['except' => 'getLogout']);
    }

    public function getLogin()
    {
        return view('auth.login');
    }

    public function postLogin(Request $request)
    {
        if ($this->auth->attempt($request->only(['email', 'password']), $request->has('remember'))) {
            return redirect('/status');
        }

        return redirect('/auth/login');
    }

    public function getRegister()
    {
        return view('auth.register');
    }

    public function postRegister(Request $request)
    {
        // Check email
        $email = $request->get('email');
        $password = $request->get('password');

        $user = User::where('email', $email)->first();

        echo User::all()->count();

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
