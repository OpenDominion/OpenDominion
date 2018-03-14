<?php

namespace OpenDominion\Services;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\User;
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
     * @param string $notificationType
     * @param array $notificationData
     */
    public function queueNotification(string $notificationType, array $notificationData)
    {
        $this->notifications[$notificationType] = $notificationData;
    }

    /**
     * Sends all queued notifications, added to the queue by queueNotification.
     *
     * @see queueNotification
     *
     * @param string $category
     * @param Dominion|User $notifiable
     */
    public function sendNotifications(string $category, $notifiable)
    {
        $user = (($notifiable instanceof Dominion) ? $notifiable->user : $notifiable);

        $emailNotifications = [];

        foreach ($this->notifications as $type => $data) {
            if ($user->getSetting("notifications.hourly_dominion.{$type}.ingame")) {
                $user->notify(new WebNotification($category, $type, $data));
            }

            if ($user->getSetting("notifications.hourly_dominion.{$type}.email")) {
                $emailNotifications[$type] = $data;
            }
        }

        if (!empty($emailNotifications)) {
//            $user->notify(new HourlyEmailDigestNotification($emailNotifications));
        }

        dd($this->notifications);

        \DB::rollBack();
        die('foo');
    }

    public function addIrregularNotification($notifiable, string $notificationType, array $notificationData)
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
