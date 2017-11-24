<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use OpenDominion\Services\Dominion\SelectorService;

class HomeController extends AbstractController
{
    public function getIndex()
    {
        // Only redirect to status/dashboard if we have no referer
        if (Auth::check() && (request()->server('HTTP_REFERER') !== '') && (url()->previous() === url()->current())) {
            $dominionSelectorService = app(SelectorService::class);

            if ($dominionSelectorService->tryAutoSelectDominionForAuthUser()) {
                return redirect()->route('dominion.status');
            }

            return redirect()->route('dashboard');
        }

        return view('pages.home');
    }
}
