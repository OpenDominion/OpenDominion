<?php

namespace OpenDominion\Http\Middleware;

use Auth;
use Closure;
use Illuminate\Http\RedirectResponse;

class LogoutUserAfterDatabaseReset
{
    public function handle($request, Closure $next)
    {
        // Check if the user and dominion records still exists. If they're gone that probably means the database has
        // been reset since their last request and they need to be logged out so they can re-register
        if (Auth::check()) {
            $user = Auth::user();

            dd([
                $user,
                $user->dominions->isEmpty(),
                $user->dominions()
            ]);

            if (($user === null) || $user->dominions->isEmpty()) {
                return $this->logout($request);
            }
        }

        dd('no auth check');

        return $next($request);
    }

    /**
     * @return RedirectResponse
     */
    protected function logout($request)
    {
        Auth::logout();
        $request->session()->flash('alert-danger', 'The database has been reset for development purposes since your last visit. Please re-register a new account. You can use the same credentials you\'ve used before.');
        return redirect()->route('home');
    }
}
