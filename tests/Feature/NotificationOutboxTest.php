<?php

namespace OpenDominion\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Mail\MailManager;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Mockery;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\NotificationOutbox;
use OpenDominion\Notifications\HourlyEmailDigestNotification;
use OpenDominion\Services\NotificationService;
use OpenDominion\Tests\AbstractTestCase;
use RuntimeException;

class NotificationOutboxTest extends AbstractTestCase
{
    protected NotificationService $service;

    protected Dominion $dominion;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mail.default' => 'array']);
        $this->dominion = $this->createDominion($this->createUser(), $this->createRound('-7 days'));
        $this->dominion->update(['protection_finished' => true]);
        $this->service = app(NotificationService::class);
    }

    public function testRepeatedOperationPreservesPayloadAndClearsBuffer(): void
    {
        $this->enableEmail();
        $id = $this->persistBatch();
        $before = $this->dominion->notifications()->count();
        $this->service->queueNotification('exploration_completed', ['land_plain' => 999]);
        $secondId = $this->service->persistQueuedNotifications($this->dominion, 'hourly_dominion', 'test-hour');

        $this->assertSame($id, $secondId);
        $this->assertSame($before, $this->dominion->notifications()->count());
        $this->assertSame(['exploration_completed' => ['land_plain' => 10]], NotificationOutbox::findOrFail($id)->payload);
        $this->assertNull($this->service->persistQueuedNotifications($this->dominion, 'hourly_dominion', 'next-hour'));
    }

    public function testRollbackRemovesWebAndEmailAndDoesNotLeakBuffer(): void
    {
        $this->enableEmail();
        Bus::fake();
        $before = $this->dominion->notifications()->count();
        $connection = $this->dominion->getConnection();
        $connection->beginTransaction();
        $id = $this->persistBatch();
        $this->assertSame($before + 1, $this->dominion->notifications()->count());
        Bus::assertNothingDispatched();
        $connection->rollBack();

        $this->assertSame($before, $this->dominion->notifications()->count());
        $this->assertNull(NotificationOutbox::find($id));
        Bus::assertNothingDispatched();
        $this->assertNull($this->service->persistQueuedNotifications($this->dominion, 'hourly_dominion', 'next-hour'));
    }

    public function testEmailReplayDoesNotDuplicateWebNotification(): void
    {
        $this->enableEmail();
        $before = $this->dominion->notifications()->count();
        $id = $this->persistBatch();
        $this->assertSame($before + 1, $this->dominion->notifications()->count());
        $this->service->deliverOutboxNotification($id);
        $this->service->deliverOutboxNotification($id);
        $this->assertSame($before + 1, $this->dominion->notifications()->count());
        $this->assertNotNull(NotificationOutbox::findOrFail($id)->delivered_at);
    }

    public function testMailFailurePreservesWebNotificationAndCanBeRetried(): void
    {
        $this->assertTrue($this->dominion->getConnection()->getPdo()->inTransaction());
        $this->enableEmail();
        $before = $this->dominion->notifications()->count();
        $id = $this->persistBatch();
        $fail = true;
        $attempts = 0;
        Event::listen(NotificationSending::class, function (NotificationSending $event) use (&$fail, &$attempts): void {
            if ($event->channel === 'mail') {
                $attempts++;
                if ($fail) {
                    throw new RuntimeException('Mail transport unavailable');
                }
            }
        });
        $handler = Mockery::mock(ExceptionHandler::class);
        $handler->shouldReceive('report')->once();
        $this->app->instance(ExceptionHandler::class, $handler);

        $this->artisan('game:notifications:deliver')->assertFailed();
        $outbox = NotificationOutbox::findOrFail($id);
        $this->assertNull($outbox->delivered_at);
        $this->assertTrue($outbox->available_at->isFuture());
        $this->assertSame($before + 1, $this->dominion->notifications()->count());
        $this->artisan('game:notifications:deliver')->assertSuccessful();
        $this->assertSame(1, $attempts);

        $fail = false;
        $outbox->update(['available_at' => now()->subMinute()]);
        $this->artisan('game:notifications:deliver')->assertSuccessful();
        $this->service->deliverOutboxNotification($id);
        $this->assertSame(2, $attempts);
        $this->assertSame($before + 1, $this->dominion->notifications()->count());
        $this->assertNotNull($outbox->fresh()->delivered_at);
    }

    public function testDeliveryHonorsCurrentPreferencesAndProtection(): void
    {
        $this->enableEmail();
        $id = $this->persistBatch();
        $this->dominion->update(['protection_finished' => false]);
        $this->dominion->user->update(['settings' => [
            'notifications' => ['hourly_dominion' => ['exploration_completed' => ['ingame' => false, 'email' => true]]],
        ]]);
        Notification::fake();
        $this->service->deliverOutboxNotification($id);
        Notification::assertNothingSent();
        $this->assertNotNull(NotificationOutbox::findOrFail($id)->delivered_at);
    }

    public function testProtectedEventsRemainIneligibleForEmailAfterProtectionEnds(): void
    {
        $this->enableEmail();
        $before = $this->dominion->notifications()->count();
        $this->dominion->getConnection()->transaction(function (): void {
            $this->dominion->update(['protection_finished' => false]);
            $this->assertNull($this->persistBatch());
            $this->dominion->update(['protection_finished' => true]);
        });

        $this->assertSame($before + 1, $this->dominion->notifications()->count());
        $this->assertSame(0, NotificationOutbox::where('dominion_id', $this->dominion->id)->count());
    }

    public function testRealmAssignmentCanStillEmailDuringProtection(): void
    {
        Notification::fake();
        $this->dominion->update(['protection_finished' => false]);
        $this->dominion->user->update(['settings' => [
            'notifications' => ['irregular_dominion' => ['realm_assignment' => ['email' => true]]],
        ]]);
        $this->service->queueNotification('realm_assignment', ['realmNumber' => 1]);
        $id = $this->service->persistQueuedNotifications($this->dominion, 'irregular_dominion', 'assignment');
        $this->service->deliverOutboxNotification($id);
        Notification::assertSentTo($this->dominion, \OpenDominion\Notifications\IrregularDominionEmailNotification::class);
    }

    public function testDeliveryCommandHonorsBatchLimitWithoutQueueJobs(): void
    {
        $this->enableEmail();
        $first = $this->persistBatch();
        $second = $this->persistBatch(null, 'next-hour');
        Notification::fake();
        Bus::fake();
        $this->artisan('game:notifications:deliver', ['--limit' => 1])->assertSuccessful();
        $this->assertNotNull(NotificationOutbox::findOrFail($first)->delivered_at);
        $this->assertNull(NotificationOutbox::findOrFail($second)->delivered_at);
        $this->artisan('game:notifications:deliver')->assertSuccessful();
        Notification::assertSentToTimes($this->dominion, HourlyEmailDigestNotification::class, 2);
        Bus::assertNothingDispatched();
    }

    public function testUnavailableQueueDoesNotAffectTickOrEmailDelivery(): void
    {
        $this->enableEmail();
        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->andThrow(new RuntimeException('Queue unavailable'));
        $this->app->instance(Dispatcher::class, $dispatcher);
        $id = $this->dominion->getConnection()->transaction(function (): int {
            return $this->persistBatch();
        });
        $this->assertNull(NotificationOutbox::findOrFail($id)->delivered_at);
        $this->artisan('game:notifications:deliver')->assertSuccessful();
        $this->assertNotNull(NotificationOutbox::findOrFail($id)->delivered_at);
    }

    public function testMaintenanceScopeDefersSeparateBatchesAndResetsOnFailure(): void
    {
        $this->enableEmail();
        $before = $this->dominion->notifications()->count();
        config(['queue.default' => 'sync']);
        Bus::fake();
        $connection = $this->dominion->getConnection();
        $connection->transaction(function (): void {
            $this->service->withDeferredDelivery('maintenance', now(), function (): void {
                for ($i = 0; $i < 2; $i++) {
                    $this->service->queueNotification('exploration_completed', ['land_plain' => $i]);
                    $this->service->sendNotifications($this->dominion, 'hourly_dominion');
                }
                Bus::assertNothingDispatched();
            });
        });
        Bus::assertNothingDispatched();
        $this->assertSame(2, NotificationOutbox::where('operation_key', 'like', 'maintenance:%')->count());
        $this->assertSame($before + 2, $this->dominion->notifications()->count());

        try {
            $this->service->withDeferredDelivery('failed', now(), function (): void {
                $this->service->queueNotification('starvation_occurred', ['peasants' => 5]);
                throw new RuntimeException('Maintenance failed');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Maintenance failed', $exception->getMessage());
        }
        $this->assertNull($this->service->persistQueuedNotifications($this->dominion, 'hourly_dominion', 'after-failure'));
        Notification::fake();
        $this->service->queueNotification('exploration_completed', ['land_plain' => 1]);
        $this->service->sendNotifications($this->dominion, 'hourly_dominion');
        Notification::assertSentToTimes($this->dominion, \OpenDominion\Notifications\WebNotification::class, 1);
    }

    public function testRecoveredDigestUsesOriginalEventHour(): void
    {
        $this->enableEmail();
        Notification::fake();
        $eventAt = Carbon::parse('2026-09-20 10:00:00');
        $id = $this->persistBatch($eventAt);
        $this->service->deliverOutboxNotification($id);
        Notification::assertSentTo($this->dominion, HourlyEmailDigestNotification::class, function ($notification) use ($eventAt): bool {
            return $notification->toMail($this->dominion)->greeting === 'Hourly Report for ' . $eventAt->format('D, M j, Y H:00');
        });
    }

    public function testDefaultPreferencesWriteWebImmediatelyWithoutOutbox(): void
    {
        $before = $this->dominion->notifications()->count();
        $this->assertNull($this->persistBatch());
        $this->assertSame($before + 1, $this->dominion->notifications()->count());
        $this->assertSame(0, NotificationOutbox::where('dominion_id', $this->dominion->id)->count());
    }

    public function testEmailPayloadFiltersEventPreferencesAndHonorsLaterOptOut(): void
    {
        $this->enableEmail();
        $this->service->queueNotification('training_completed', ['military_unit1' => 5]);
        $id = $this->persistBatch();
        $this->assertSame(['exploration_completed' => ['land_plain' => 10]], NotificationOutbox::findOrFail($id)->payload);
        $this->dominion->user->update(['settings' => [
            'notifications' => ['hourly_dominion' => [
                'exploration_completed' => ['email' => false],
                'training_completed' => ['email' => true],
            ]],
        ]]);
        Notification::fake();
        $this->service->deliverOutboxNotification($id);
        Notification::assertNothingSent();
        $this->assertNotNull(NotificationOutbox::findOrFail($id)->delivered_at);
    }

    public function testCommandStopsStartingRowsAfterTimeBudget(): void
    {
        $this->enableEmail();
        $first = $this->persistBatch();
        $second = $this->persistBatch(null, 'next-hour');
        $service = Mockery::mock(NotificationService::class);
        $service->shouldReceive('deliverOutboxNotification')->once()->with($first)->andReturnUsing(function () use ($first): void {
            NotificationOutbox::whereKey($first)->update(['delivered_at' => now()]);
            usleep(1_100_000);
        });
        $this->app->instance(NotificationService::class, $service);
        $this->artisan('game:notifications:deliver', ['--max-seconds' => 1])->assertSuccessful();
        $this->assertNull(NotificationOutbox::findOrFail($second)->delivered_at);
    }

    public function testCommandBoundsOnlyItsOwnSmtpTransportAndRestoresDefault(): void
    {
        $this->enableEmail();
        $id = $this->persistBatch();
        config(['mail.default' => 'smtp', 'mail.outbox_smtp_timeout' => 7]);
        $originalTimeout = config('mail.mailers.smtp.timeout');
        $service = Mockery::mock(NotificationService::class);
        $service->shouldReceive('deliverOutboxNotification')->once()->with($id)->andReturnUsing(function (): void {
            $transport = app(MailManager::class)->mailer()->getSymfonyTransport();
            $this->assertSame(7.0, $transport->getStream()->getTimeout());
        });
        $this->app->instance(NotificationService::class, $service);
        $this->artisan('game:notifications:deliver')->assertSuccessful();
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame($originalTimeout, config('mail.mailers.smtp.timeout'));
    }

    protected function persistBatch(?Carbon $eventAt = null, string $operationKey = 'test-hour'): ?int
    {
        $this->service->queueNotification('exploration_completed', ['land_plain' => 10]);
        return $this->service->persistQueuedNotifications($this->dominion, 'hourly_dominion', $operationKey, $eventAt);
    }

    protected function enableEmail(): void
    {
        $this->dominion->user->update(['settings' => [
            'notifications' => ['hourly_dominion' => ['exploration_completed' => ['email' => true]]],
        ]]);
    }
}
