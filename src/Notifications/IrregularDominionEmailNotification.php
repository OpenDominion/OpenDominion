<?php

namespace OpenDominion\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use OpenDominion\Helpers\NotificationHelper;
use OpenDominion\Models\Dominion;

class IrregularDominionEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @var NotificationHelper */
    protected $notificationHelper;

    /** @var array */
    protected $notifications;

    /** @var Carbon */
    protected $now;

    /**
     * IrregularDominionEmailNotification constructor.
     *
     * @param array $notifications
     */
    public function __construct(array $notifications)
    {
        $this->notificationHelper = app(NotificationHelper::class);
        $this->notifications = $notifications;
        $this->now = now();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param Dominion $dominion
     * @return array
     */
    public function via(Dominion $dominion): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param Dominion $dominion
     * @return MailMessage
     */
    public function toMail(Dominion $dominion): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->replyTo('internetfett@gmail.com', 'InternetFett')
            ->subject($this->getSubject())
            ->greeting('Irregular Dominion Event(s) at ' . $this->now->format('D, M j, Y H:00'))
            ->line('Hello ' . $dominion->user->display_name . '!')
            ->line('The following dominion event(s) just occurred in your dominion *' . $dominion->name . '*:');

        foreach ($this->notifications as $notification) {
            $mailMessage = $mailMessage->line('- ' . $this->notificationHelper->getNotificationMessage(
                $notification['category'],
                $notification['type'],
                $notification['data']
            ));
        }

        $mailMessage = $mailMessage->line('You are receiving this email because you have turned on email notifications for one or more of the above events.')
            ->line('To unsubscribe, please update your notification settings at: ' . route('settings'))
            ->salutation('-OpenDominion');

        return $mailMessage;
    }

    // todo: move to parent abstract class
    protected function getSubject(): string
    {
        $subjectParts[] = '[OD]';

        $amountNotifications = count($this->notifications);
        if ($amountNotifications > 1) {
            $subjectParts[] = ('(+' . ($amountNotifications - 1) . ')');
        }

        $firstNotification = array_first($this->notifications);

        $subjectParts[] = $this->notificationHelper->getNotificationMessage(
            $firstNotification['category'],
            $firstNotification['type'],
            $firstNotification['data']
        );

        return implode(' ', $subjectParts);
    }
}
