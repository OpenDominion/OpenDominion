<?php

namespace OpenDominion\Notifications;

use Illuminate\Notifications\Notification;
use OpenDominion\Helpers\NotificationHelper;
use OpenDominion\Models\Dominion;

class WebNotification extends Notification
{
    /** @var NotificationHelper */
    protected $notificationHelper;

    /** @var string */
    protected $category;

    /** @var string */
    protected $type;

    /** @var array */
    protected $data;

    /**
     * WebNotification constructor.
     *
     * @param string $category
     * @param string $type
     * @param array $data
     */
    public function __construct(string $category, string $type, array $data)
    {
        $this->notificationHelper = app(NotificationHelper::class);
        $this->category = $category;
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param Dominion $dominion
     * @return array
     */
    public function via(Dominion $dominion): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param Dominion $dominion
     * @return array
     */
    public function toArray(Dominion $dominion): array
    {
        return [
            'category' => $this->category,
            'type' => $this->type,
            'message' => $this->notificationHelper->getNotificationMessage(
                $this->category,
                $this->type,
                $this->data
            ),
        ];
    }
}
