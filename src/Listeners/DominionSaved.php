<?php

namespace OpenDominion\Listeners;

use DB;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Events\DominionSavedEvent;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\TickService;

class DominionSaved
{
    /**
     * Handle the event.
     *
     * @param  DominionSavedEvent  $event
     * @return void
     */
    public function handle(DominionSavedEvent $event)
    {
        $dominion = $event->dominion->fresh();

        // Abort if round has ended
        if ($dominion->round->hasEnded()) {
            return;
        }

        // Update networth
        $networthCalculator = app(NetworthCalculator::class);
        $networth = $networthCalculator->getDominionNetworth($dominion, true);

        if ($dominion->calculated_networth !== $networth) {
            DB::table('dominions')
                ->where('id', $dominion->id)
                ->update([
                    'calculated_networth' => $networth
                ]);
        }

        // Recalculate next tick
        $tickService = app(TickService::class);
        $tickService->precalculateTick($dominion);
    }
}
