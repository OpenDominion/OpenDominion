<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use OpenDominion\Contracts\Services\Dominion\SelectorService;

class HomeController extends AbstractController
{
    public function getIndex()
    {
        // Only redirect to status/dashboard if we have no referer
        if (Auth::check() && !request()->server('HTTP_REFERER')) {
            $dominionSelectorService = app(SelectorService::class);

            if ($dominionSelectorService->hasUserSelectedDominion()) {
                return redirect()->route('dominion.status');
            }

            return redirect()->route('dashboard');
        }

        return view('pages.home');
    }
}
