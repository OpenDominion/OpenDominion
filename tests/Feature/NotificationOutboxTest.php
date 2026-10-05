<?php

namespace OpenDominion\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Mockery;
use OpenDominion\Jobs\DeliverNotificationOutbox;
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
        $id = $this->persistBatch();
        $this->service->queueNotification('exploration_completed', ['land_plain' => 999]);
        $secondId = $this->service->persistQueuedNotifications($this->dominion, 'hourly_dominion', 'test-hour');

        $this->assertSame($id, $secondId);
        $this->assertSame(['exploration_completed' => ['land_plain' => 10]], NotificationOutbox::findOrFail($id)->payload);
        $this->assertNull($this->service->persistQueuedNotifications($this->dominion, 'hourly_dominion', 'next-hour'));
    }

    public function testRollbackRemovesOutboxAndDispatchAndDoesNotLeakBuffer(): void
    {
        Bus::fake();
        $connection = $this->dominion->getConnection();
        $connection->beginTransaction();
        $id = $this->persistBatch();
        $this->service->dispatchOutboxNotifications([$id]);
        Bus::assertNothingDispatched();
        $connection->rollBack();

        $this->assertNull(NotificationOutbox::find($id));
        Bus::assertNothingDispatched();
        $this->assertNull($this->service->persistQueuedNotifications($this->dominion, 'hourly_dominion', 'next-hour'));
    }

    public function testDeliveryCreatesWebNotificationOnlyOnce(): void
    {
        $id = $this->persistBatch();
        $before = $this->dominion->notifications()->count();
        $this->service->deliverOutboxNotification($id);
        $this->service->deliverOutboxNotification($id);

        $this->assertSame($before + 1, $this->dominion->notifications()->count());
        $this->assertNotNull(NotificationOutbox::findOrFail($id)->delivered_at);
    }

    public function testMailFailurePreservesWebReceiptAndCanBeRetried(): void
    {
        $this->enableEmail();
        $id = $this->persistBatch();
        $before = $this->dominion->notifications()->count();
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

        try {
            (new DeliverNotificationOutbox($id))->handle($this->service);
            $this->fail('Expected mail delivery failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Mail transport unavailable', $exception->getMessage());
        }
        $outbox = NotificationOutbox::findOrFail($id);
        $this->assertNotNull($outbox->web_delivered_at);
        $this->assertNull($outbox->delivered_at);
        $this->assertTrue($outbox->available_at->isFuture());
        $this->assertSame($before + 1, $this->dominion->notifications()->count());

        $fail = false;
        $this->service->deliverOutboxNotification($id);
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
        Notification::fake();
        $this->dominion->getConnection()->transaction(function (): void {
            $this->dominion->update(['protection_finished' => false]);
            $this->persistBatch();
            $this->dominion->update(['protection_finished' => true]);
        });

        $outbox = NotificationOutbox::where('operation_key', 'test-hour')->firstOrFail();
        $this->assertFalse($outbox->email_allowed);
        $this->service->deliverOutboxNotification($outbox->id);
        Notification::assertNotSentTo($this->dominion, HourlyEmailDigestNotification::class);
        Notification::assertSentToTimes($this->dominion, \OpenDominion\Notifications\WebNotification::class, 1);
        $this->assertNotNull($outbox->fresh()->delivered_at);
    }

    public function testRealmAssignmentCanStillEmailDuringProtection(): void
    {
        Notification::fake();
        $this->dominion->update(['protection_finished' => false]);
        $this->dominion->user->update(['settings' => [
            'notifications' => ['irregular_dominion' => ['realm_assignment' => ['email' => true]]],
        ]]);
        $this->service->queueNotification('realm_assignment', ['realm_number' => 1]);
        $id = $this->service->persistQueuedNotifications($this->dominion, 'irregular_dominion', 'assignment');
        $this->assertFalse(NotificationOutbox::findOrFail($id)->email_allowed);
        $this->service->deliverOutboxNotification($id);
        Notification::assertSentTo($this->dominion, \OpenDominion\Notifications\IrregularDominionEmailNotification::class);
    }

    public function testRecoveryCommandQueuesPendingBatches(): void
    {
        Bus::fake();
        $id = $this->persistBatch();
        $this->artisan('game:notifications:deliver')->assertSuccessful();
        Bus::assertDispatched(DeliverNotificationOutbox::class, fn ($job) => $job->outboxId === $id);
        Bus::assertDispatchedTimes(DeliverNotificationOutbox::class, 1);
        $this->artisan('game:notifications:deliver')->assertSuccessful();
        Bus::assertDispatchedTimes(DeliverNotificationOutbox::class, 1);
    }

    public function testQueueFailureDoesNotEscapeCommittedTick(): void
    {
        $id = $this->persistBatch();
        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->once()->andThrow(new RuntimeException('Queue unavailable'));
        $this->app->instance(Dispatcher::class, $dispatcher);
        $handler = Mockery::mock(ExceptionHandler::class);
        $handler->shouldReceive('report')->once();
        $this->app->instance(ExceptionHandler::class, $handler);

        $this->dominion->getConnection()->transaction(function () use ($id): void {
            $this->service->dispatchOutboxNotifications([$id]);
        });
        $this->assertNull(NotificationOutbox::findOrFail($id)->delivered_at);
    }

    public function testMaintenanceScopeDefersSeparateBatchesAndResetsOnFailure(): void
    {
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

    protected function persistBatch(?Carbon $eventAt = null): int
    {
        $this->service->queueNotification('exploration_completed', ['land_plain' => 10]);
        return $this->service->persistQueuedNotifications($this->dominion, 'hourly_dominion', 'test-hour', $eventAt);
    }

    protected function enableEmail(): void
    {
        $this->dominion->user->update(['settings' => [
            'notifications' => ['hourly_dominion' => ['exploration_completed' => ['email' => true]]],
        ]]);
    }
}
