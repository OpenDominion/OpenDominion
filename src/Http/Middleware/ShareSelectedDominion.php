<?php

namespace OpenDominion\Http\Middleware;

use Closure;
use OpenDominion\Repositories\DominionRepository;

class ShareSelectedDominion
{
    /** @var DominionRepository */
    protected $dominions;

    function __construct(DominionRepository $dominions)
    {
        $this->dominions = $dominions;
    }

    public function handle($request, Closure $next)
    {
        $selectedDominionId = session('selected_dominion_id');

        if ($selectedDominionId) {
            $dominion = $this->dominions->find($selectedDominionId);

            view()->share('selectedDominion', $dominion);
        }

        return $next($request);
    }
}
