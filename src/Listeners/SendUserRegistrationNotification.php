<?php

namespace OpenDominion\Listeners;

use OpenDominion\Events\OpenDominionEventsUserRegisteredEvent;
use OpenDominion\Events\UserRegisteredEvent;
use OpenDominion\Notifications\UserRegisteredNotification;

class SendUserRegistrationNotification
{
    /**
     * Handle the event.
     *
     * @param UserRegisteredEvent $event
     * @return void
     */
    public function handle(UserRegisteredEvent $event): void
    {
        $event->user->notify(new UserRegisteredNotification);
    }
}
