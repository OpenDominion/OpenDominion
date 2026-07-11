<?php

namespace OpenDominion\Listeners;

use OpenDominion\Events\UserRegisteredEvent;
use OpenDominion\Helpers\NotificationHelper;
use OpenDominion\Services\NewPlayerTourService;

class SetUserDefaultSettings
{
    /** @var NotificationHelper */
    protected $notificationHelper;

    /** @var NewPlayerTourService */
    protected $newPlayerTourService;

    /**
     * SetUserDefaultSettings constructor.
     *
     * @param NotificationHelper $notificationHelper
     * @param NewPlayerTourService $newPlayerTourService
     */
    public function __construct(NotificationHelper $notificationHelper, NewPlayerTourService $newPlayerTourService)
    {
        $this->notificationHelper = $notificationHelper;
        $this->newPlayerTourService = $newPlayerTourService;
    }

    /**
     * Handle the event.
     *
     * @param UserRegisteredEvent $event
     * @return void
     */
    public function handle(UserRegisteredEvent $event): void
    {
        $user = $event->getUser();

        $settings = [
            NewPlayerTourService::SETTING_KEY => $this->newPlayerTourService->defaultState(),
        ];

        // Notifications
        $settings['notifications'] = $this->notificationHelper->getDefaultUserNotificationSettings();
        $settings['notification_digest'] = 'hourly';

        $user->settings = $settings;
        $user->save();
    }
}
