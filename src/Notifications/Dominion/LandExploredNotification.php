<?php

namespace OpenDominion\Notifications\Dominion;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use OpenDominion\Models\Dominion;

class LandExploredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @var array */
    protected $data;

    /**
     * LandExploredNotification constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
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
        $user = $dominion->user;

        return array_merge(
            $user->getSetting('notifications.hourly_dominion.exploration_completed.ingame') ? ['database'] : [],
            $user->getSetting('notifications.hourly_dominion.exploration_completed.email') ? ['mail'] : []
        );
    }

    /**
     * Get the array representation of the notification.
     *
     * @param Dominion $dominion
     * @return array
     */
    public function toArray(Dominion $dominion): array
    {
        return ['message' => $this->getMessage()];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param Dominion $dominion
     * @return MailMessage
     */
    public function toMail(Dominion $dominion): MailMessage
    {
        return (new MailMessage)
            ->replyTo('email@wavehack.net', 'WaveHack')
            ->subject('[OD] Land Exploration Completed')
            ->greeting('Land Exploration Completed')
            ->line('Hello ' . $dominion->user->display_name . '!')
            ->line($this->getMessage())
            ->action('View exploration page', route('dominion.explore'))
            ->line('You are receiving this email because you have turned on email notifications for completing land exploration.')
            ->line('To unsubscribe, please update your notification settings at: ' . route('settings'))
            ->salutation('-OpenDominion');
    }

    protected function getMessage(): string
    {
        $acres = array_sum($this->data);

        return sprintf(
            'Exploration of %s %s of land completed.',
            number_format($acres),
            str_plural('acre', $acres)
        );
    }
}
