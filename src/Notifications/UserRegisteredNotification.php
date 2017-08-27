<?php

namespace OpenDominion\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use OpenDominion\Models\User;

class UserRegisteredNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
            ->subject('OpenDominion Registration')
            ->greeting('OpenDominion Registration')
            ->line($user->display_name)
            ->line('You are receiving this email because someone using this email address recently registered for the free online strategy / war game OpenDominion. If you did not register for OpenDominion, don\'t worry, the person using this email address will need to click the activation link below to continue playing.')
            ->line('If you did indeed register for OpenDominion, then welcome to the game! Please click the activation link below because you *will need to click it*, and there is no way to activate your account other than contacting the owner if you delete this message.')
            ->action('Activate your account', route('auth.activate', $user->activation_code))
            ->line('You can find OpenDominion at: ' . route('home'))
            ->line('Thank you for playing, and have fun!')
            ->salutation('-OpenDominion');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param User $user
     * @return array
     */
    public function toArray(User $user): array
    {
        return [
            //
        ];
    }
}
