<?php

namespace OpenDominion\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenDominion\Services\DominionSelectorService;

class ShareSelectedDominion
{
    /** @var DominionSelectorService */
    protected $dominionSelectorService;

    /**
     * ShareSelectedDominion constructor.
     *
     * @param DominionSelectorService $dominionSelectorService
     */
    public function __construct(DominionSelectorService $dominionSelectorService)
    {
        $this->dominionSelectorService = $dominionSelectorService;
    }

    public function handle($request, Closure $next)
    {
        if ($this->dominionSelectorService->hasUserSelectedDominion()) {
            try {
                $dominion = $this->dominionSelectorService->getUserSelectedDominion();

            } catch (ModelNotFoundException $e) {
                $this->dominionSelectorService->unsetUserSelectedDominion();

                $request->session()->flash('alert-danger', 'The database has been reset for development purposes since your last visit. Please re-register a new account. You can use the same credentials you\'ve used before.');

                return redirect()->route('home');
            }

            view()->share('selectedDominion', $dominion);
        }

        return $next($request);
    }
}
