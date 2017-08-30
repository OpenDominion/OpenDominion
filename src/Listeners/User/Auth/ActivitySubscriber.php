<?php

namespace OpenDominion\Listeners\User\Auth;

use Illuminate\Events\Dispatcher;
use OpenDominion\Events\UserLoggedInEvent;
use OpenDominion\Listeners\Subscribers\SubscriberInterface;
use OpenDominion\Services\Activity\ActivityService;

class ActivitySubscriber implements SubscriberInterface
{
    /** @var ActivityService */
    protected $userActivityService;

    public function __construct(ActivityService $userActivityService)
    {
        $this->userActivityService = $userActivityService;
    }

    // todo: refactor

    public function onLogin(UserLoggedInEvent $event)
    {
        $this->userActivityService->recordActivity($event->user, 'auth.login');
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(UserLoggedInEvent::class, (static::class . '@onLogin'));
    }
}
