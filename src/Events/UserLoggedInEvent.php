<?php

namespace OpenDominion\Events;

use OpenDominion\Contracts\Services\AnalyticsService\Event as EventContract;
use OpenDominion\Services\AnalyticsService\Event;

class UserLoggedInEvent extends AbstractUserEvent implements AnalyticsEvent
{
    public function getAnalyticsEvent(): EventContract
    {
        return new Event('user', 'login');
    }
}
