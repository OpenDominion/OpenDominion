<?php

namespace OpenDominion\Events;

use OpenDominion\Contracts\Services\Analytics\AnalyticsEvent as EventContract;
use OpenDominion\Services\Analytics\AnalyticsEvent;

class UserLoggedInEvent extends AbstractUserEvent implements AnalyticsEvent
{
    public function getAnalyticsEvent(): EventContract
    {
        return new AnalyticsEvent('user', 'login');
    }
}
