<?php

namespace OpenDominion\Http\Middleware;

use Bugsnag;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
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
        if (!$this->dominionSelectorService->hasUserSelectedDominion()) {
            $dominion = $this->dominionSelectorService->tryAutoSelectDominionForAuthUser();

            if (!$dominion) {
                return redirect()->guest('dashboard');
            }
        }

        try {
            $dominion = $this->dominionSelectorService->getUserSelectedDominion();
        } catch (ModelNotFoundException $e) {
            $this->dominionSelectorService->unsetUserSelectedDominion();

            $request->session()->flash('alert-danger', 'The database has been reset for development purposes since your last visit. Please re-register a new account. You can use the same credentials you\'ve used before.');

            return redirect()->route('home');
        }

        Bugsnag::registerCallback(function (Bugsnag\Report $report) use ($dominion) {
            /** @noinspection NullPointerExceptionInspection */
            $report->setMetaData([
                'dominion' => Arr::except($dominion->toArray(), ['race', 'realm']),
            ]);
        });

        view()->share('selectedDominion', $dominion);

        return $next($request);
    }
}
