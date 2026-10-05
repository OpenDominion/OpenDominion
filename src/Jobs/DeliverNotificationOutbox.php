<?php

namespace OpenDominion\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use OpenDominion\Models\NotificationOutbox;
use OpenDominion\Services\NotificationService;
use Throwable;

class DeliverNotificationOutbox implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public int $backoff = 60;

    public function __construct(public int $outboxId)
    {
        $this->afterCommit();
    }

    public function handle(NotificationService $notificationService): void
    {
        try {
            $notificationService->deliverOutboxNotification($this->outboxId);
        } catch (Throwable $exception) {
            NotificationOutbox::query()->whereKey($this->outboxId)->whereNull('delivered_at')
                ->update(['available_at' => now()->addMinutes(5)]);
            throw $exception;
        }
    }
}
