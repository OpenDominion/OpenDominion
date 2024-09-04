<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Helpers\RankingsHelper;
use OpenDominion\Helpers\WonderHelper;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\RankingsService;

class WorldController extends AbstractDominionController
{
    public function getIndex(Request $request)
    {
        $dominion = $this->getSelectedDominion();

        if (!$dominion->round->hasStarted()) {
            $request->session()->flash('alert-warning', 'You cannot view other realms before the round begins.');
            return redirect()->back();
        }

        $blackGuard = $dominion->round->dominions()
            ->where('black_guard_active_at', '<', now())
            ->where(function ($query) {
                $query->where('black_guard_inactive_at', null)
                    ->orWhere('black_guard_inactive_at', '>', now());
            })
            ->get()
            ->countBy('realm_id');

        $rankingsService = app(RankingsService::class);
        $rankings = $rankingsService->getRankingsByRealm($dominion->round);

        $realms = $dominion->round->realms()
            ->with([
                'dominions',
                'warsIncoming' => function ($q) {
                    $q->active();
                },
                'warsIncoming.sourceRealm',
                'warsOutgoing' => function ($q) {
                    $q->active();
                },
                'warsOutgoing.targetRealm',
                'wonders'
            ])
            ->where('number', '!=', 0)
            ->get()
            ->sortBy('number');

        $wonders = $dominion->round->wonders()
            ->with(['damage', 'realm', 'wonder', 'wonder.perks'])
            ->get()
            ->sortBy('wonder.name');

        return view('pages.dominion.world', [
            'governmentService' => app(GovernmentService::class),
            'landCalculator' => app(LandCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),
            'blackGuard' => $blackGuard,
            'rankings' => $rankings,
            'rankingsHelper' => app(RankingsHelper::class),
            'realms' => $realms,
            'wonderHelper' => app(WonderHelper::class),
            'wonders' => $wonders,
        ]);
    }
}
