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
        // Work on a clone so the caller's in-memory model isn't affected by
        // the relation reloads + attribute mutations performed inside
        // NetworthCalculator and TickService::precalculateTick. The clone
        // inherits the caller's eager-loaded relations, avoiding the lazy-load
        // cascade we'd hit if we re-fetched from scratch. Anything we lazy-load
        // from here on (round, queues, etc.) lands on the clone, not the caller.
        $dominion = clone $event->dominion;

        // Abort if round has ended
        if ($dominion->round->hasEnded()) {
            return;
        }

        // Queues may have been mutated during the save (QueueService writes
        // rows directly) and networth counts in-flight units, so reload them.
        $dominion->load('queues');

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
