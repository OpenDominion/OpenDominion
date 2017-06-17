<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use OpenDominion\Services\DominionSelectorService;

class HomeController extends AbstractController
{
    public function getIndex()
    {
        if (Auth::check()) {
            $dominionSelectorService = app(DominionSelectorService::class);

            if ($dominionSelectorService->hasUserSelectedDominion()) {
                return redirect()->route('dominion.status');
            }

            return redirect()->route('dashboard');
        }

        return view('pages.home');
    }
}
