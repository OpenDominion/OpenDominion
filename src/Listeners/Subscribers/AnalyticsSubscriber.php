<?php

namespace OpenDominion\Listeners\Subscribers;

use Illuminate\Events\Dispatcher;
use OpenDominion\Events\UserActivatedEvent;
use OpenDominion\Events\UserLoggedInEvent;
use OpenDominion\Events\UserRegisteredEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Analytics\HasAnalyticsEvent;

class AnalyticsSubscriber implements SubscriberInterface
{
    /** @var AnalyticsService */
    protected $analyticsService;

    /** @var string[] */
    protected $events = [
        UserActivatedEvent::class,
        UserLoggedInEvent::class,
        UserRegisteredEvent::class,
    ];

    /**
     * AnalyticsSubscriber constructor.
     *
     * @param AnalyticsService $analyticsService
     */
    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen($this->events, function (HasAnalyticsEvent $event) {
            $this->analyticsService->queueFlashEvent($event->getAnalyticsEvent());
        });
    }
}
