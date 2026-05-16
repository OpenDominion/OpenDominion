<?php

namespace OpenDominion\Listeners;

use Illuminate\Queue\Events\QueueBusy;
use Illuminate\Support\Facades\Mail;

class SendQueueBusyAlert
{
    /**
     * Handle the event.
     *
     * @param QueueBusy $event
     * @return void
     */
    public function handle(QueueBusy $event): void
    {
        Mail::raw(
            "Queue '{$event->queue}' on connection '{$event->connectionName}' has {$event->size} pending jobs.",
            function ($message) {
                $message->to('info@opendominion.net')
                    ->subject('OpenDominion queue backlog alert');
            }
        );
    }
}
