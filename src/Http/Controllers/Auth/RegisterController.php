<?php

namespace OpenDominion\Http\Controllers\Auth;

use Illuminate\Foundation\Auth\RedirectsUsers;
use Illuminate\Http\Request;
use Mail;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Mail\UserRegistrationMail;
use OpenDominion\Models\User;
use OpenDominion\Repositories\UserRepository;
use OpenDominion\Services\AnalyticsService;
use Session;
use Validator;

class RegisterController extends AbstractController
{
    use RedirectsUsers;

    /** @var UserRepository */
    protected $users;

    protected $redirectTo = '/';

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function getRegister()
    {
        return view('pages.auth.register');
    }

    public function postRegister(Request $request)
    {
        $this->validator($request->all())->validate();

        $user = $this->create($request->all());

        Mail::to($user->email)->send(new UserRegistrationMail($user));

        // todo: fire laravel event
        $analyticsService = resolve(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsService\Event(
            'user',
            'register'
        ));

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

        $analyticsService = resolve(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsService\Event(
            'user',
            'activate'
        ));

        Session::flash('alert-success', 'Your account has been activated and you are now logged in.');

        return redirect()->route('dashboard');
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'display_name' => 'required|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
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
