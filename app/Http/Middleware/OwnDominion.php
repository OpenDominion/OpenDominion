<?php

namespace OpenDominion\Http\Middleware;

use Auth;
use Closure;

class OwnDominion
{
    public function handle($request, Closure $next)
    {
        $dominion = $request->route()->getParameter('dominion');

        if ($dominion->user_id != Auth::user()->id) {
            return response('Unauthorized', 401);
        }

        return $next($request);
    }
}
