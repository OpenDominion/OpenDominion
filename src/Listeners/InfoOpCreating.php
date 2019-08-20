<?php

namespace OpenDominion\Listeners;

use OpenDominion\Events\InfoOpCreatingEvent;
use OpenDominion\Models\InfoOp;

class InfoOpCreating
{
    /**
     * Handle the event.
     *
     * @param  InfoOpCreatingEvent  $event
     * @return void
     */
    public function handle(InfoOpCreatingEvent $event)
    {
        if ($event->infoOp->type == 'clairvoyance') {
            InfoOp::where('target_realm_id', '=', $event->infoOp->target_realm_id)
                ->where('source_realm_id', '=', $event->infoOp->source_realm_id)
                ->where('type', '=', 'clairvoyance')
                ->update(['latest' => false]);
        } else {
            InfoOp::where('target_dominion_id', '=', $event->infoOp->target_dominion_id)
                ->where('source_realm_id', '=', $event->infoOp->source_realm_id)
                ->where('type', '=', $event->infoOp->type)
                ->update(['latest' => false]);
        }

    }
}
