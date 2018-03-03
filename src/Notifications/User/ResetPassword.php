<?php

namespace OpenDominion\Notifications\User;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use OpenDominion\Models\User;

class ResetPassword extends Notification implements ShouldQueue
{
    use Queueable;

    /** @var string */
    public $token;

    /**
     * ResetPassword constructor.
     *
     * @param string $token
     */
    public function __construct(string $token)
    {
        $this->token = $token;
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
        return (new MailMessage)
            ->replyTo('email@wavehack.net', 'WaveHack')
            ->subject('OpenDominion Password Reset')
            ->greeting('OpenDominion Password Reset')
            ->line('Hello ' . $user->display_name . '!')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', route('auth.password.reset', $this->token))
            ->line('If you did not request a password reset, no further action is required.')
            ->salutation('-OpenDominion');
    }
}
