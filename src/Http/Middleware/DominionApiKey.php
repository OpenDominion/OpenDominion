<?php

namespace OpenDominion\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use OpenDominion\Models\Dominion;
use Symfony\Component\HttpFoundation\Response;

class DominionApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->extractKey($request);

        if ($key === null || $key === '') {
            return $this->error('missing_api_key', 'Provide an API key via the X-API-Key header.', 401);
        }

        $dominion = Dominion::with(['round', 'realm'])
            ->where('api_key', $key)
            ->first();

        if ($dominion === null) {
            return $this->error('invalid_api_key', 'The provided API key was not recognised.', 401);
        }

        if ($dominion->locked_at !== null) {
            return $this->error('dominion_locked', 'Locked dominions cannot access the API.', 403);
        }

        if ($dominion->round->hasEnded()) {
            return $this->error('round_ended', 'This dominion\'s round has ended; the API key is no longer active.', 410);
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
