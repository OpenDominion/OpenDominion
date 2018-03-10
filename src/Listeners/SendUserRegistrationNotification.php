<?php

namespace OpenDominion\Listeners;

use OpenDominion\Events\UserRegisteredEvent;
use OpenDominion\Notifications\User\RegisteredNotification;

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
        $event->getUser()->notify(new RegisteredNotification);
    }
}
