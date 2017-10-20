<?php

namespace OpenDominion\Http\Middleware;

use Closure;
use OpenDominion\Services\Dominion\SelectorService;

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
        if (!$this->dominionSelectorService->hasUserSelectedDominion() && !$this->dominionSelectorService->tryAutoSelectDominionForAuthUser()) {
            return redirect()->guest('dashboard');
        }

        return $next($request);
    }
}
