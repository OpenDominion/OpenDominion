<?php

namespace OpenDominion\Listeners;

use DB;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Events\DominionSavedEvent;
use OpenDominion\Models\Dominion;

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
        $dominion = Dominion::with('queues')->find($event->dominion->id);

        $networthCalculator = app(NetworthCalculator::class);
        $networth = $networthCalculator->getDominionNetworth($dominion, true);

        DB::table('dominions')
            ->where('id', $dominion->id)
            ->update([
                'calculated_networth' => $networth
            ]);
    }
}
