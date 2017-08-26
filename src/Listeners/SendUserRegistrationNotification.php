<?php

namespace OpenDominion\Listeners;

use OpenDominion\Events\OpenDominionEventsUserRegisteredEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use OpenDominion\Events\UserRegisteredEvent;

class SendUserRegistrationNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param UserRegisteredEvent $event
     * @return void
     */
    public function handle(UserRegisteredEvent $event): void
    {
        //
    }
}
