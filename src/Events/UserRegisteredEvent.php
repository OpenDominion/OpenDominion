<?php

namespace OpenDominion\Events;

use OpenDominion\Contracts\Services\Analytics\AnalyticsEvent as AnalyticsEventContract;
use OpenDominion\Services\Analytics\AnalyticsEvent;

class UserRegisteredEvent extends AbstractUserEvent implements HasAnalyticsEvent, HasAuditEvent
{
    /**
     * {@inheritdoc}
     */
    public function getAnalyticsEvent(): AnalyticsEventContract
    {
        return new AnalyticsEvent('user', 'register');
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditEvent()
    {
        // todo
//        return new AuditEvent('user.register');
    }
}
