<?php

namespace OpenDominion\Console\Commands;

use Illuminate\Console\Command;
use OpenDominion\Models\NotificationOutbox;
use OpenDominion\Services\NotificationService;

class DeliverPendingNotifications extends Command
{
    protected $signature = 'game:notifications:deliver {--limit=100 : Maximum pending batches to dispatch}';

    protected $description = 'Recover delivery of committed hourly notification batches';

    public function handle(NotificationService $notificationService): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($limit === false) {
            $this->error('The limit must be a positive integer.');
            return self::FAILURE;
        }

        $ids = NotificationOutbox::query()->whereNull('delivered_at')
            ->where('available_at', '<=', now())
            ->orderBy('available_at')->orderBy('id')->limit($limit)->pluck('id')->all();
        $notificationService->dispatchOutboxNotifications($ids);

        $this->info(count($ids) . ' notification batches submitted for delivery.');
        return self::SUCCESS;
    }
}
