<?php

namespace OpenDominion\Listeners\Subscribers;

use Illuminate\Events\Dispatcher;
use OpenDominion\Contracts\Services\Analytics\AnalyticsService;
use OpenDominion\Events\HasAnalyticsEvent;
use OpenDominion\Events\UserRegisteredEvent;

class AnalyticsSubscriber implements SubscriberInterface
{
    /** @var AnalyticsService */
    protected $analyticsService;

    /** @var string[] */
    protected $events = [
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
