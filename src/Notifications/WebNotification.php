<?php

namespace OpenDominion\Notifications;

use Illuminate\Notifications\Notification;
use LogicException;
use OpenDominion\Helpers\NotificationHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\User;

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
     * @param Dominion|User $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        if (!($notifiable instanceof Dominion || $notifiable instanceof User)) {
            throw new LogicException('Can only send WebNotification to Dominion or User');
        }

        $user = (($notifiable instanceof Dominion) ? $notifiable->user : $notifiable);

        return ($user->getSetting("notifications.{$this->category}.{$this->type}.ingame") ? ['database'] : []);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param Dominion|User $notifiable
     * @return array
     */
    public function toArray($notifiable): array
    {
        return ['message' => $this->getMessage()];
    }

    protected function getMessage(): string
    {
        switch ("{$this->category}.{$this->type}") {

            case 'hourly_dominion.exploration_completed':
                $acres = array_sum($this->data);

                return sprintf(
                    'Exploration of %s %s of land completed',
                    number_format($acres),
                    str_plural('acre', $acres)
                );

            case 'hourly_dominion.construction_completed':
                $buildings = array_sum($this->data);

                return sprintf(
                    'Construction of %s %s completed',
                    number_format($buildings),
                    str_plural('building', $buildings)
                );

            default:
                throw new LogicException("Unknown WebNotification message for type {$this->type} with category {$this->category}");

        }

        // exploration/construction/training/returning = sum
        // spell = spell name
        // invasion/spyop/spell = other dom name
        // scripted = sum/amount of acres
        // realmie invasion = instigator, target
        // war = other realm name
        // wonder = wondername, attacker
        // realmie death = realmie dom name
    }
}
