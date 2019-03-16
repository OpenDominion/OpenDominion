<?php

namespace OpenDominion\Services;

use OpenDominion\Models\Dominion;
use OpenDominion\Notifications\HourlyEmailDigestNotification;
use OpenDominion\Notifications\IrregularDominionEmailNotification;
use OpenDominion\Notifications\WebNotification;

class NotificationService
{
    /** @var array */
    protected $notifications = [];

    /**
     * Queues a notification, to be sent later with sendNotifications.
     *
     * @see sendNotifications
     *
     * @param string $type
     * @param array $data
     */
    public function queueNotification(string $type, array $data)
    {
        $this->notifications[$type] = $data;
    }

    /**
     * Sends all queued notifications, added to the queue by queueNotification.
     *
     * @see queueNotification
     *
     * @param Dominion $dominion
     * @param string $category
     */
    public function sendNotifications(Dominion $dominion, string $category)
    {
        $user = $dominion->user;

        $emailNotifications = [];

        foreach ($this->notifications as $type => $data) {
            if ($user->getSetting("notifications.{$category}.{$type}.ingame")) {
                $dominion->notify(new WebNotification($category, $type, $data));
            }

            if ($user->getSetting("notifications.{$category}.{$type}.email")) {
                $emailNotifications[] = [
                    'category' => $category,
                    'type' => $type,
                    'data' => $data,
                ];
            }
        }

        if (!empty($emailNotifications)) {
            switch ($category) {
                case 'general':
                    throw new \LogicException('todo');
                case 'hourly_dominion':
                    $dominion->notify(new HourlyEmailDigestNotification($emailNotifications));
                    break;

                case 'irregular_dominion':
                    $dominion->notify(new IrregularDominionEmailNotification($emailNotifications));
                    break;

//                case 'irregular_realm':
//                    $dominion->notify(new IrregularRealmEmailNotification($emailNotifications));
//                    break;
            }

        }

        $this->notifications = [];
    }

    public function addIrregularNotification(Dominion $dominion, string $notificationType, array $notificationData)
    {
        // add notification to the db (notification_queue?)
    }

    public function processIrregularNotifications()
    {
        // ...
    }

    protected function sendIrregularNotification($notifiable /* ... */)
    {
        // ...
    }
}
