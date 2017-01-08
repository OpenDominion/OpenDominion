<?php

namespace OpenDominion\Http\Middleware;

use Closure;
use OpenDominion\Services\DominionSelectorService;

class DominionSelected
{
    /** @var DominionSelectorService */
    protected $dominionSelectorService;

    function __construct(DominionSelectorService $dominionSelectorService)
    {
        $this->dominionSelectorService = $dominionSelectorService;
    }

    public function handle($request, Closure $next)
    {
        if (!$this->dominionSelectorService->hasUserSelectedDominion()) {
            return redirect(route('dashboard'));
        }

        return $next($request);
    }
}
