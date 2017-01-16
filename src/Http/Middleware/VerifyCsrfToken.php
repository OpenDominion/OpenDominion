<?php

namespace OpenDominion\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        //
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Don't verify CSRF token during Windows development due to random TokenMismatchExceptions being thrown
        if ((app()->environment() === 'local') && (PHP_OS === 'WINNT')) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
