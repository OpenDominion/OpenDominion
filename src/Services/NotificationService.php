<?php

namespace OpenDominion\Services;

use OpenDominion\Models\Dominion;
use OpenDominion\Notifications\HourlyEmailDigestNotification;
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
            if ($user->getSetting("notifications.hourly_dominion.{$type}.ingame")) {
                $dominion->notify(new WebNotification($category, $type, $data));
            }

            if ($user->getSetting("notifications.hourly_dominion.{$type}.email")) {
                $emailNotifications[] = [
                    'category' => $category,
                    'type' => $type,
                    'data' => $data,
                ];
            }
        }

        if (!empty($emailNotifications)) {
            $dominion->notify(new HourlyEmailDigestNotification($emailNotifications));
        }
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
