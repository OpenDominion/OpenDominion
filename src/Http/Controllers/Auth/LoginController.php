<?php

namespace OpenDominion\Http\Controllers\Auth;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use OpenDominion\Events\UserFailedLoginEvent;
use OpenDominion\Events\UserLoggedInEvent;
use OpenDominion\Events\UserLoggedOutEvent;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\User;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\SelectorService;

class LoginController extends AbstractController
{
    use AuthenticatesUsers {
        logout as traitLogout;
        sendFailedLoginResponse as protected traitSendFailedLoginResponse;
    }

    protected $redirectTo = '/dominion/status';

    /**
     * {@inheritdoc}
     */
    public function showLoginForm()
    {
        return view('pages.auth.login');
    }

    /**
     * {@inheritdoc}
     */
    protected function authenticated(Request $request, User $user)
    {
        event(new UserLoggedInEvent($user));

        $selectorService = app(SelectorService::class);
        $selectorService->tryAutoSelectDominionForAuthUser();
    }

    /**
     * {@inheritdoc}
     */
    public function logout(Request $request)
    {
        event(new UserLoggedOutEvent(auth()->user()));

        $response = $this->traitLogout($request);

        session()->flash('alert-success', 'You have been logged out.');

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        // Register user activity if a user with supplied email exists
        $user = User::where('email', $request->get('email'))->first();

        if ($user) {
            event(new UserFailedLoginEvent($user));
        }

        return $this->traitSendFailedLoginResponse($request);
    }
}
