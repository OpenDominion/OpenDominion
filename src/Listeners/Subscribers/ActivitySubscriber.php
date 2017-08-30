<?php

namespace OpenDominion\Listeners\Subscribers;

use Illuminate\Events\Dispatcher;
use OpenDominion\Contracts\Services\Activity\ActivityService;
use OpenDominion\Events\UserActivatedEvent;
use OpenDominion\Events\UserLoggedInEvent;
use OpenDominion\Events\UserRegisteredEvent;
use OpenDominion\Services\Activity\HasActivityEvent;

class ActivitySubscriber implements SubscriberInterface
{
    /** @var ActivityService */
    protected $activityService;

    /** @var string[] */
    protected $events = [
        UserActivatedEvent::class,
        UserLoggedInEvent::class,
        UserRegisteredEvent::class,
    ];

    /**
     * ActivitySubscriber constructor.
     *
     * @param ActivityService $activityService
     */
    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen($this->events, function (HasActivityEvent $event) {
            $this->activityService->recordActivity($event->getUser(), $event->getActivityEvent());
        });
    }
}
