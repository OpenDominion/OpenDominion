<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use Illuminate\Http\RedirectResponse;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\SelectorService;
use OpenDominion\Services\NewPlayerTourService;

class NewPlayerTourController extends AbstractController
{
    public function postAdvance(NewPlayerTourService $tour, SelectorService $selector): RedirectResponse
    {
        $dominion = $this->selectedDominion($selector);
        $currentUrl = $tour->getCurrentStepUrl(Auth::user(), $dominion);
        $advanced = $tour->advance(Auth::user(), $dominion);
        $nextUrl = $tour->getCurrentStepUrl(Auth::user(), $dominion);

        if (!$advanced) {
            session()->flash('alert-warning', 'Complete the current field-guide objective before continuing.');
        }

        return $nextUrl !== null || $currentUrl !== null
            ? redirect($nextUrl ?? $currentUrl)
            : redirect()->back();
    }

    public function postBack(NewPlayerTourService $tour, SelectorService $selector): RedirectResponse
    {
        $tour->goBack(Auth::user());
        return redirect($this->currentUrl($tour, $selector));
    }

    public function postSkip(NewPlayerTourService $tour): RedirectResponse
    {
        $tour->skip(Auth::user());
        return redirect()->back();
    }

    public function postRestart(NewPlayerTourService $tour, SelectorService $selector): RedirectResponse
    {
        $tour->restart(Auth::user());
        return redirect($this->currentUrl($tour, $selector));
    }

    private function currentUrl(NewPlayerTourService $tour, SelectorService $selector): string
    {
        return $tour->getCurrentStepUrl(Auth::user(), $this->selectedDominion($selector)) ?? route('dashboard');
    }

    private function selectedDominion(SelectorService $selector): ?Dominion
    {
        return $selector->hasUserSelectedDominion() ? $selector->getUserSelectedDominion() : null;
    }
}
