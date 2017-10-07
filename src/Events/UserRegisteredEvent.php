<?php

namespace OpenDominion\Events;

use OpenDominion\Services\Activity\ActivityEvent;
use OpenDominion\Services\Activity\HasActivityEvent;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\HasAnalyticsEvent;

class UserRegisteredEvent extends AbstractUserEvent implements HasActivityEvent, HasAnalyticsEvent
{
    /**
     * {@inheritdoc}
     */
    public function getActivityEvent(): ActivityEvent
    {
        // todo: ioc
        return new ActivityEvent('user.register', ActivityEvent::STATUS_SUCCESS);
    }

    /**
     * {@inheritdoc}
     */
    public function getAnalyticsEvent(): AnalyticsEvent
    {
        // todo: ioc
        return new AnalyticsEvent('user', 'register');
    }
}
