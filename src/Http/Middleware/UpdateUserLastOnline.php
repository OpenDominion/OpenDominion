<?php

namespace OpenDominion\Http\Middleware;

use Auth;
use Carbon\Carbon;
use Closure;

class UpdateUserLastOnline
{
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $user->timestamps = false;
            $user->last_online = new Carbon();
            $user->save();
        }

        return $next($request);
    }
}
