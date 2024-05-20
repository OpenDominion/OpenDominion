<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Helpers\WonderHelper;
use OpenDominion\Services\Dominion\GovernmentService;

class WorldController extends AbstractDominionController
{
    public function getIndex()
    {
        $dominion = $this->getSelectedDominion();

        $realms = $dominion->round->realms()
            ->with([
                'dominions',
                'warsIncoming' => function($q) {
                    $q->active();
                },
                'warsIncoming.sourceRealm',
                'warsOutgoing' => function($q) {
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
            'realms' => $realms,
            'wonderHelper' => app(WonderHelper::class),
            'wonders' => $wonders,
        ]);
    }
}
