<?php

namespace OpenDominion\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use OpenDominion\Models\Dominion;
use Symfony\Component\HttpFoundation\Response;

class DominionApiKey
{
    /**
     * With $mode "optional" a request without a key continues unauthenticated;
     * a key that is sent is still validated.
     */
    public function handle(Request $request, Closure $next, string $mode = 'required'): Response
    {
        $key = $this->extractKey($request);

        if ($key === null || $key === '') {
            if ($mode === 'optional') {
                app()->forgetInstance('api.dominion');

                return $next($request);
            }

            return $this->error('missing_api_key', 'Provide an API key via the X-API-Key header.', 401);
        }

        $dominion = Dominion::with(['round', 'realm'])
            ->where('api_key', $key)
            ->first();

        if ($dominion === null) {
            return $this->error('invalid_api_key', 'The provided API key was not recognised.', 401);
        }

        if ($dominion->round->hasEnded()) {
            return $this->error('round_ended', 'This dominion\'s round has ended; the API key is no longer active.', 410);
        }

        if ($dominion->isLocked()) {
            return $this->error('dominion_locked', 'Locked dominions cannot access the API.', 403);
        }

        app()->instance('api.dominion', $dominion);

        return $next($request);
    }

    private function extractKey(Request $request): ?string
    {
        $header = $request->header('X-API-Key');
        if (is_string($header) && $header !== '') {
            return trim($header);
        }

        $bearer = $request->bearerToken();
        if (is_string($bearer) && $bearer !== '') {
            return $bearer;
        }

        return null;
    }

    private function error(string $code, string $message, int $status): Response
    {
        return response()->json([
            'error' => $code,
            'message' => $message,
        ], $status);
    }
}
