<?php

namespace OpenDominion\Http\Middleware;

use Auth;
use Closure;

class UpdateUserLastOnline
{
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->last_online === null || $user->last_online < now()->subMinute()) {
                $user->timestamps = false;
                $user->last_online = now();
                $user->save();
            }
        }

        return $next($request);
    }
}
