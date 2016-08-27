<?php

namespace OpenDominion\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $auth = Auth::guard($guard);

        if ($auth->guest()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response('Unauthorized.', 401);
            } else {
                return redirect()->guest(route('auth.login'));
            }
        }

        if (!$auth->user()->activated) {
            $auth->logout();
            // todo: add "click here to have a new activation email being sent to you"
            $request->session()->flash('alert-danger', 'Your account has not been activated yet. Check your spam folder for the activation email.');
            return redirect()->guest(route('auth.login'));
        }

        return $next($request);
    }
}
