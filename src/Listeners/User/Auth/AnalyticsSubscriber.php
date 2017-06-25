<?php

namespace OpenDominion\Listeners\User\Auth;

use Illuminate\Events\Dispatcher;
use OpenDominion\Contracts\Services\AnalyticsService;
use OpenDominion\Events\UserLoginEvent;
use OpenDominion\Services\AnalyticsService\Event;

class AnalyticsSubscriber implements SubscriberInterface
{
    /** @var AnalyticsService */
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function onLogin(UserLoginEvent $event)
    {
        $this->analyticsService->queueFlashEvent(new Event('user', 'login'));
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(UserLoginEvent::class, (static::class . '@onLogin'));
    }
}
