<?php

namespace OpenDominion\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use OpenDominion\Models\User;

class ManualEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @var string */
    protected $subject;

    /** @var string */
    protected $greeting;

    /** @var array */
    protected $lines;

    /** @var array */
    protected $action;

    /**
     * ManualEmailNotification constructor.
     *
     * @param string $subject
     * @param string $greeting
     * @param array $lines
     * @param array|null $action [Label => URL]
     */
    public function __construct(string $subject, string $greeting, array $lines, ?array $action = null)
    {
        $this->subject = $subject;
        $this->greeting = $greeting;
        $this->lines = $lines;
        $this->action = $action;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param User $user
     * @return array
     */
    public function via(User $user): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param User $user
     * @return MailMessage
     */
    public function toMail(User $user): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->replyTo('info@opendominion.net', 'OpenDominion')
            ->subject($this->subject)
            ->greeting($this->greeting);

        foreach ($this->lines as $line) {
            $mailMessage = $mailMessage->line($line);
        }

        if ($this->action !== null) {
            foreach ($this->action as $label => $url) {
                $mailMessage = $mailMessage->action($label, $url);
            }
        }

        $mailMessage = $mailMessage->line('You are receiving this email because you have turned on email notifications for generic emails manually sent by the administrators.')
            ->line('To unsubscribe, please update your notification settings at: ' . route('settings'))
            ->salutation('-OpenDominion');

        return $mailMessage;
    }
}
