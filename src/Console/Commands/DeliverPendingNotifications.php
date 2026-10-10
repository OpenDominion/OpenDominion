<?php

namespace OpenDominion\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Mail\MailManager;
use OpenDominion\Models\NotificationOutbox;
use OpenDominion\Services\NotificationService;
use Throwable;

class DeliverPendingNotifications extends Command
{
    protected $signature = 'game:notifications:deliver
        {--limit=100 : Maximum pending email batches to deliver}
        {--max-seconds=30 : Stop starting new deliveries after this many seconds}';

    protected $description = 'Deliver committed notification emails independently of game ticks';

    public function handle(NotificationService $notificationService): int
    {
        $options = ['options' => ['min_range' => 1]];
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, $options);
        $maxSeconds = filter_var($this->option('max-seconds'), FILTER_VALIDATE_INT, $options);
        if ($limit === false || $maxSeconds === false) {
            $this->error('The limit and max-seconds must be positive integers.');
            return self::FAILURE;
        }

        $mailer = config('mail.default');
        $mailerConfig = config("mail.mailers.{$mailer}", []);
        $usesSmtp = ($mailerConfig['transport'] ?? null) === 'smtp';
        if ($usesSmtp) {
            $timeout = (float) config('mail.outbox_smtp_timeout', 10);
            if ($timeout <= 0 || !is_finite($timeout)) {
                $this->error('The outbox SMTP timeout must be a positive number.');
                return self::FAILURE;
            }
            config([
                'mail.mailers.notification_outbox' => array_replace($mailerConfig, ['timeout' => $timeout]),
                'mail.default' => 'notification_outbox',
            ]);
        }

        $started = hrtime(true);
        $delivered = 0;
        $failed = 0;
        try {
            $ids = NotificationOutbox::query()->whereNull('delivered_at')
                ->where('available_at', '<=', now())
                ->orderBy('available_at')->orderBy('id')->limit($limit)->pluck('id');
            foreach ($ids as $id) {
                if ((hrtime(true) - $started) / 1_000_000_000 >= $maxSeconds) {
                    break;
                }
                try {
                    $notificationService->deliverOutboxNotification($id);
                    $delivered++;
                } catch (Throwable $exception) {
                    NotificationOutbox::query()->whereKey($id)->whereNull('delivered_at')
                        ->update(['available_at' => now()->addMinutes(5)]);
                    report($exception);
                    $failed++;
                }
            }
        } finally {
            if ($usesSmtp) {
                config(['mail.default' => $mailer]);
                app(MailManager::class)->purge('notification_outbox');
            }
        }

        $this->info("{$delivered} email batches completed; {$failed} deferred for retry.");
        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
