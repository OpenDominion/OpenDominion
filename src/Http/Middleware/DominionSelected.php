<?php

namespace OpenDominion\Http\Middleware;

use Closure;
use OpenDominion\Contracts\Services\Dominion\SelectorService;

class DominionSelected
{
    /** @var SelectorService */
    protected $dominionSelectorService;

    public function __construct(SelectorService $dominionSelectorService)
    {
        $this->dominionSelectorService = $dominionSelectorService;
    }

    public function handle($request, Closure $next)
    {
        if (!$this->dominionSelectorService->hasUserSelectedDominion()) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
