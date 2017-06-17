<?php

namespace OpenDominion\Listeners\User\Auth;

use Illuminate\Events\Dispatcher;
use OpenDominion\Events\UserLoginEvent;
use OpenDominion\Services\User\ActivityService;

class ActivitySubscriber implements SubscriberInterface
{
    /** @var ActivityService */
    protected $userActivityService;

    public function __construct(ActivityService $userActivityService)
    {
        $this->userActivityService = $userActivityService;
    }

    public function onLogin(UserLoginEvent $event)
    {
        $this->userActivityService->recordActivity($event->user, 'auth.login');
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(UserLoginEvent::class, (static::class . '@onLogin'));
    }
}
