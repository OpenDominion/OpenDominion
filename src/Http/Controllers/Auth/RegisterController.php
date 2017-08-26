<?php

namespace OpenDominion\Http\Controllers\Auth;

use Illuminate\Foundation\Auth\RedirectsUsers;
use Illuminate\Http\Request;
use OpenDominion\Contracts\Services\Analytics\AnalyticsService;
use OpenDominion\Events\UserRegisteredEvent;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Jobs\SendUserRegistrationMail;
use OpenDominion\Models\User;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use Session;
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

    public function getRegister()
    {
        return view('pages.auth.register');
    }

    public function postRegister(Request $request)
    {
        $this->validator($request->all())->validate();

        event(new UserRegisteredEvent($user = $this->create($request->all())));

        $request->session()->flash('alert-success', 'You have been successfully registered. An activation email has been dispatched to your address.');

        return redirect($this->redirectPath());
    }

    public function getActivate($activation_code)
    {
        $users = $this->users->findWhere([
            'activated' => false,
            'activation_code' => $activation_code,
        ]);

        if ($users->isEmpty()) {
            Session::flash('alert-danger', 'Invalid activation code');

            return redirect($this->redirectPath());
        }

        $user = $users->first();
        $user->activated = true;
        $user->save();

        auth()->login($user);

        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsEvent(
            'user',
            'activate'
        ));

        Session::flash('alert-success', 'Your account has been activated and you are now logged in.');

        return redirect()->route('dashboard');
    }

    protected function activated(Request $request, $user)
    {
//        dispatch(new SendUserActivatedMail($user));

//        event(new UserActivatedEvent($user));

//        Session::flash('alert-success', 'Your account has been activated and you are now logged in.');

//        return redirect()->route('dashboard'); //?
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'display_name' => 'required|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
            'terms' => 'required',
        ]);
    }

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
