<?php

namespace OpenDominion\Http\Middleware;

use Closure;

class DominionSelected
{
    public function handle($request, Closure $next)
    {
        if (!session('dominion_id')) {
            return redirect(route('dashboard'));
            // todo: throw 401?
        }

        return $next($request);
    }
}
