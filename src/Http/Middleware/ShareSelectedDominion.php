<?php

namespace OpenDominion\Http\Middleware;

use Closure;
use OpenDominion\Services\DominionSelectorService;

class ShareSelectedDominion
{
    /** @var DominionSelectorService */
    protected $dominionSelectorService;

    public function __construct(DominionSelectorService $dominionSelectorService)
    {
        $this->dominionSelectorService = $dominionSelectorService;
    }

    public function handle($request, Closure $next)
    {
        if ($this->dominionSelectorService->hasUserSelectedDominion()) {
            $dominion = $this->dominionSelectorService->getUserSelectedDominion();

            foreach (app()->tagged('calculators') as $calculator) {
                $calculator->init($dominion);
            }

            view()->share('selectedDominion', $dominion);
        }

        return $next($request);
    }
}
