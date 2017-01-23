<?php

namespace OpenDominion\Http\Controllers\Auth;

use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\User;
use OpenDominion\Services\AnalyticsService;

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

    protected function authenticated(Request $request, User $user)
    {
        // todo: fire laravel event
        $analyticsService = app()->make(AnalyticsService::class);

        $analyticsService->queueFlashEvent(new AnalyticsService\Event(
            'user',
            'login'
        ));
    }

    public function postLogout(Request $request)
    {
        $response = $this->logout($request);

        // todo: fire laravel event
        $analyticsService = app()->make(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsService\Event(
            'user',
            'logout'
        ));

        session()->flash('alert-success', 'You have been logged out.');

        return $response;
    }
}
