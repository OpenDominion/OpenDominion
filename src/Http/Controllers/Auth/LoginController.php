<?php

namespace OpenDominion\Http\Controllers\Auth;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use OpenDominion\Events\UserFailedLoginEvent;
use OpenDominion\Events\UserLoggedInEvent;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\User;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\SelectorService;

class LoginController extends AbstractController
{
    use AuthenticatesUsers {
        sendFailedLoginResponse as protected traitSendFailedLoginResponse;
    }

    protected $redirectTo = '/dominion/status';

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
        event(new UserLoggedInEvent($user));

        // todo: refactorme
        // Makeshift fix to redirect user to active dominion status if user has only one active dominion, instead of
        // dashboard
        $activeDominions = $user->dominions()
            ->join('rounds', 'rounds.id', 'dominions.round_id')
            ->where('rounds.start_date', '<=', Carbon::now())
            ->where('rounds.end_date', '>', Carbon::now())
            ->get(['dominions.*']);

        if ($activeDominions->count() === 1) {
            $selectorService = app(SelectorService::class);
            $selectorService->selectUserDominion($activeDominions->first());
        }
    }

    public function postLogout(Request $request)
    {
//        event(new UserLogoutEvent(auth()->user()));

        $response = $this->logout($request);

        // todo: fire laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsEvent(
            'user',
            'logout'
        ));

        session()->flash('alert-success', 'You have been logged out.');

        return $response;
    }

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
