<?php

namespace OpenDominion\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Bus\Dispatcher;
use OpenDominion\Helpers\NotificationHelper;
use OpenDominion\Jobs\DeliverNotificationOutbox;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\NotificationOutbox;
use OpenDominion\Notifications\HourlyEmailDigestNotification;
use OpenDominion\Notifications\IrregularDominionEmailNotification;
use OpenDominion\Notifications\WebNotification;
use Throwable;

class NotificationService
{
    /** @var array */
    protected $notifications = [];

    protected ?string $deferredOperationKey = null;

    protected ?Carbon $deferredEventAt = null;

    protected int $deferredSequence = 0;

    /** @var NotificationHelper */
    protected $notificationHelper;

    /**
     * NotificationService constructor.
     */
    public function __construct()
    {
        $this->notificationHelper = app(NotificationHelper::class);
    }

    /**
     * Queues a notification, to be sent later with sendNotifications.
     *
     * @see sendNotifications
     *
     * @param string $type
     * @param array $data
     * @return NotificationService
     */
    public function queueNotification(string $type, array $data = []): self
    {
        $this->notifications[$type] = $data;

        return $this;
    }

    /**
     * Persist notifications emitted by hourly maintenance for the delivery command.
     */
    public function withDeferredDelivery(string $operationKey, Carbon $eventAt, callable $callback): mixed
    {
        if ($this->deferredOperationKey !== null) {
            throw new \LogicException('Notification delivery scopes cannot be nested.');
        }
        $this->deferredOperationKey = $operationKey;
        $this->deferredEventAt = $eventAt->copy();
        $this->deferredSequence = 0;
        $this->resetQueuedNotifications();
        try {
            return $callback();
        } finally {
            $this->deferredOperationKey = null;
            $this->deferredEventAt = null;
            $this->deferredSequence = 0;
            $this->resetQueuedNotifications();
        }
    }

    public function resetQueuedNotifications(): void
    {
        $this->notifications = [];
    }

    /**
     * Persist the buffered events in the same transaction as their game changes.
     * Reusing an operation key preserves the original payload.
     */
    public function persistQueuedNotifications(Dominion $dominion, string $category, string $operationKey, ?Carbon $eventAt = null): ?int
    {
        try {
            if (empty($this->notifications) || $dominion->user_id === null) {
                return null;
            }
            if (!in_array($category, ['hourly_dominion', 'irregular_dominion', 'irregular_realm'], true)) {
                throw new \InvalidArgumentException('Unsupported notification outbox category.');
            }

            return NotificationOutbox::query()->firstOrCreate([
                'operation_key' => $operationKey,
                'dominion_id' => $dominion->id,
                'category' => $category,
            ], [
                'payload' => $this->notifications,
                'email_allowed' => $dominion->protection_finished,
                'event_at' => $eventAt ?? now(),
                'available_at' => now(),
            ])->id;
        } finally {
            $this->resetQueuedNotifications();
        }
    }

    /**
     * Queue committed batches. The recovery command covers crashes and queue outages.
     *
     * @param int[] $ids
     */
    public function dispatchOutboxNotifications(array $ids): void
    {
        $dispatch = function () use ($ids): void {
            foreach (array_unique($ids) as $id) {
                try {
                    NotificationOutbox::query()->whereKey($id)->whereNull('delivered_at')
                        ->update(['available_at' => now()->addMinutes(5)]);
                    app(Dispatcher::class)->dispatch(new DeliverNotificationOutbox($id));
                } catch (Throwable $exception) {
                    report($exception);
                }
            }
        };

        $connection = (new NotificationOutbox)->getConnection();
        if ($connection->transactionLevel() > 0) {
            $connection->afterCommit($dispatch);
        } else {
            $dispatch();
        }
    }

    /**
     * Web notifications and their receipt commit together. Email delivery is at
     * least once: a process crash after the mail server accepts it can repeat it.
     * Only this outbox row is locked during delivery, never the game tick.
     */
    public function deliverOutboxNotification(int $id): void
    {
        $connection = (new NotificationOutbox)->getConnection();
        $connection->transaction(function () use ($id): void {
            $outbox = NotificationOutbox::query()->lockForUpdate()->find($id);
            if ($outbox === null || $outbox->web_delivered_at !== null || $outbox->delivered_at !== null) {
                return;
            }
            $dominion = $outbox->dominion;
            if ($dominion !== null && $dominion->user !== null) {
                foreach ($outbox->payload as $type => $data) {
                    if ($this->notificationEnabled($dominion, $outbox->category, $type, 'ingame')) {
                        $dominion->notify(new WebNotification($outbox->category, $type, $data));
                    }
                }
            }
            $outbox->web_delivered_at = now();
            $outbox->save();
        });

        $connection->transaction(function () use ($id): void {
            $outbox = NotificationOutbox::query()->lockForUpdate()->find($id);
            if ($outbox === null || $outbox->delivered_at !== null) {
                return;
            }
            $dominion = $outbox->dominion;
            $emails = [];
            if ($dominion !== null && $dominion->user !== null) {
                foreach ($outbox->payload as $type => $data) {
                    if ((!$outbox->email_allowed || !$dominion->protection_finished) && $type !== 'realm_assignment') {
                        continue;
                    }
                    if ($this->notificationEnabled($dominion, $outbox->category, $type, 'email')) {
                        $emails[] = ['category' => $outbox->category, 'type' => $type, 'data' => $data];
                    }
                }
            }
            if ($emails !== []) {
                $notification = $outbox->category === 'hourly_dominion'
                    ? new HourlyEmailDigestNotification($emails, $outbox->event_at)
                    : new IrregularDominionEmailNotification($emails);
                $dominion->notifyNow($notification);
            }
            $outbox->delivered_at = now();
            $outbox->save();
        });
    }

    public function notificationEnabled(Dominion $dominion, string $category, string $type, string $channel): bool
    {
        $setting = $dominion->user->getSetting("notifications.{$category}.{$type}.{$channel}");
        return (bool) ($setting ?? $this->notificationHelper->getDefaultUserNotificationSettings()[$category][$type][$channel] ?? false);
    }

    /**
     * Sends all queued notifications, added to the queue by queueNotification.
     *
     * @see queueNotification
     *
     * @param Dominion $dominion
     * @param string $category
     */
    public function sendNotifications(Dominion $dominion, string $category): void
    {
        if ($this->deferredOperationKey !== null) {
            $this->persistQueuedNotifications(
                $dominion,
                $category,
                $this->deferredOperationKey . ':' . ++$this->deferredSequence,
                $this->deferredEventAt
            );
            return;
        }

        $user = $dominion->user;
        if ($user == null) {
            // Clear notifications queued for Non-Player Dominions
            $this->notifications = [];
            return;
        }

        $emailNotifications = [];
        $defaultSettings = $this->notificationHelper->getDefaultUserNotificationSettings();

        foreach ($this->notifications as $type => $data) {
            $ingameSetting = $user->getSetting("notifications.{$category}.{$type}.ingame");
            if ($ingameSetting === null) {
                $ingameSetting = $defaultSettings[$category][$type]['ingame'];
            }
            if ($ingameSetting) {
                $dominion->notify(new WebNotification($category, $type, $data));
            }

            if (!$dominion->protection_finished && $type !== 'realm_assignment') {
                // Disable email notfications during protection
                continue;
            }

            $emailSetting = $user->getSetting("notifications.{$category}.{$type}.email");
            if ($emailSetting === null) {
                $emailSetting = $defaultSettings[$category][$type]['email'];
            }
            if ($emailSetting) {
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
                    $notification = new IrregularDominionEmailNotification($emailNotifications);
                    dispatch(function () use ($dominion, $notification) {
                        $dominion->notify($notification);
                    })->afterResponse();
                    break;

                case 'irregular_realm':
                    $notification = new IrregularDominionEmailNotification($emailNotifications);
                    dispatch(function () use ($dominion, $notification) {
                        $dominion->notify($notification);
                    })->afterResponse();
                    break;
            }

        }

        $this->notifications = [];
    }

//    public function addIrregularNotification(Dominion $dominion, string $notificationType, array $notificationData): void
//    {
//        // add notification to the db (notification_queue?)
//    }
//
//    public function processIrregularNotifications(): void
//    {
//        // ...
//    }
//
//    protected function sendIrregularNotification($notifiable /* ... */): void
//    {
//        // ...
//    }
}
