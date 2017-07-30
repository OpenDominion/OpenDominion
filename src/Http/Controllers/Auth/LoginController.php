<?php

namespace OpenDominion\Http\Controllers\Auth;

use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use OpenDominion\Contracts\Services\AnalyticsService;
use OpenDominion\Contracts\Services\Dominion\SelectorService;
use OpenDominion\Events\UserLoginEvent;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\User;
use OpenDominion\Services\AnalyticsService\Event;

class LoginController extends AbstractController
{
    use AuthenticatesUsers;

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
        event(new UserLoginEvent($user));

        // todo: refactor to something like dominionSelectorService->trySelectActiveDominion()
//        if ($user->hasActiveDominion()) {
//            app(DominionSelectorService::class)
//                ->selectUserDominion($user->getActiveDominion());
//        }

        if ($user->dominions->count() === 1) {
            /** @var SelectorService $dominionSelectorService */
            $dominionSelectorService = app(SelectorService::class);
            $dominionSelectorService->selectUserDominion($user->dominions->first());
        }
    }

    public function postLogout(Request $request)
    {
//        event(new UserLogoutEvent(auth()->user()));

        $response = $this->logout($request);

        // todo: fire laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new Event(
            'user',
            'logout'
        ));

        session()->flash('alert-success', 'You have been logged out.');

        return $response;
    }
}
