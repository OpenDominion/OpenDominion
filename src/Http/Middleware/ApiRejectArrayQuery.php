<?php

namespace OpenDominion\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The V1 API only takes scalar query parameters. Rejects array syntax such as
 * ?since[]=x up front, so controllers never receive an array where they
 * expect a string.
 */
class ApiRejectArrayQuery
{
    public function handle(Request $request, Closure $next): Response
    {
        foreach ($request->query() as $name => $value) {
            if (is_array($value)) {
                return response()->json([
                    'error' => 'invalid_parameter',
                    'message' => sprintf('The "%s" parameter must be a single value, not an array.', $name),
                ], 422);
            }
        }

        return $next($request);
    }
}
