<?php

namespace OpenDominion\Events;

use OpenDominion\Contracts\Services\Analytics\AnalyticsEvent as AnalyticsEventContract;
use OpenDominion\Services\Analytics\AnalyticsEvent;

//use OpenDominion\Events\AnalyticsEvent;

class UserRegisteredEvent extends AbstractUserEvent implements HasAnalyticsEvent, HasAuditEvent
{
    public function getAnalyticsEvent(): AnalyticsEventContract
    {
        return new AnalyticsEvent('user', 'register');
//        return new AnalyticsEvent('user', 'register');
    }

    public function getAuditEvent()
    {
//        return new AuditEvent('user.register');
    }
}
