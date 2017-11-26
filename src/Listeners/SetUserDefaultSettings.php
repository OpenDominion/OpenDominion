<?php

namespace OpenDominion\Listeners;

use OpenDominion\Events\UserRegisteredEvent;
use OpenDominion\Helpers\NotificationHelper;

class SetUserDefaultSettings
{
    /** @var NotificationHelper */
    protected $notificationHelper;

    /**
     * SetUserDefaultSettings constructor.
     *
     * @param NotificationHelper $notificationHelper
     */
    public function __construct(NotificationHelper $notificationHelper)
    {
        $this->notificationHelper = $notificationHelper;
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

        $settings = [];

        // Notifications
        $settings['notifications'] = [];

        foreach (collect([
            $this->notificationHelper->getGeneralTypes(),
            $this->notificationHelper->getHourlyDominionTypes(),
            $this->notificationHelper->getIrregularDominionTypes()
        ]) as $notifications) {
            foreach ($notifications as $notificationKey => $notification) {
                $settings['notifications'][$notificationKey] = collect($notification['defaults'])->filter(function ($value) {
                    return $value === true;
                });
            }
        }

        // Notification Settings
        $settings['notification_digest'] = 'hourly';

        $user->settings = $settings;
        $user->save();
    }
}
