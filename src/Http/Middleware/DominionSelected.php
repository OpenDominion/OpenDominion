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
        // Nothing to do
        if ($this->dominionSelectorService->hasUserSelectedDominion()) {
            return $next($request);
        }

        $dominion = $this->dominionSelectorService->tryAutoSelectDominionForAuthUser();

        if (!$dominion) {
            return redirect()->guest('dashboard');
        }

        // Manually call ShareSelectedDominion middleware again
        return app(ShareSelectedDominion::class)->handle($request, $next);
    }
}
