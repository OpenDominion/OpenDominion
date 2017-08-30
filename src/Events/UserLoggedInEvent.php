<?php

namespace OpenDominion\Events;

use OpenDominion\Contracts\Services\Analytics\AnalyticsEvent as EventContract;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\HasAnalyticsEvent;

class UserLoggedInEvent extends AbstractUserEvent implements HasAnalyticsEvent
{
    public function getAnalyticsEvent(): EventContract
    {
        return new AnalyticsEvent('user', 'login');
    }
}
