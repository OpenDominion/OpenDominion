<?php

namespace OpenDominion\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use OpenDominion\Models\Round;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks API endpoints until the round they expose has started. The round is
 * taken from the {round} route parameter, or from the API key's dominion.
 */
class ApiRoundStarted
{
    public function handle(Request $request, Closure $next): Response
    {
        $round = $this->resolveRound($request);

        if ($round !== null && !$round->hasStarted()) {
            return response()->json([
                'error' => 'round_not_started',
                'message' => 'This endpoint is not available until the round has started.',
            ], 403);
        }

        return $next($request);
    }

    private function resolveRound(Request $request): ?Round
    {
        $round = $request->route('round');
        if ($round instanceof Round) {
            return $round;
        }

        if (app()->bound('api.dominion')) {
            return app('api.dominion')->round;
        }

        return null;
    }
}
