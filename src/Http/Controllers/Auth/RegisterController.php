<?php

namespace OpenDominion\Http\Controllers\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\RedirectsUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use OpenDominion\Events\UserActivatedEvent;
use OpenDominion\Events\UserRegisteredEvent;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\User;
use Validator;

class RegisterController extends AbstractController
{
    use RedirectsUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    public function getRegister(): View
    {
        return view('pages.auth.register');
    }

    public function postRegister(Request $request): RedirectResponse
    {
        $this->validator($request->all())->validate();

        event(new UserRegisteredEvent($user = $this->create($request->all())));

        $request->session()->flash('alert-success', 'You have been successfully registered. An activation email has been dispatched to your address.');

        return redirect($this->redirectPath());
    }

    public function getActivate(Request $request, string $activation_code): RedirectResponse
    {
        $user = User::where([
            'activated' => false,
            'activation_code' => $activation_code,
        ])->firstOrFail();

        $user->activated = true;
        $user->save();

        auth()->login($user);

        event(new UserActivatedEvent($user));

        $request->session()->flash('alert-success', 'Your account has been activated and you are now logged in.');

        return redirect()->route('dashboard');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     * @return \Illuminate\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'display_name' => 'required|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
            'terms' => 'required',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
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
