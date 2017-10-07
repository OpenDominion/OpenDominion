<?php

namespace OpenDominion\Events;

use OpenDominion\Services\Activity\ActivityEvent;
use OpenDominion\Services\Activity\HasActivityEvent;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\HasAnalyticsEvent;

class UserLoggedInEvent extends AbstractUserEvent implements HasActivityEvent, HasAnalyticsEvent
{
    /**
     * {@inheritdoc}
     */
    public function getActivityEvent(): ActivityEvent
    {
        return new ActivityEvent('user.login', ActivityEvent::STATUS_SUCCESS);
    }

    /**
     * {@inheritdoc}
     */
    public function getAnalyticsEvent(): AnalyticsEvent
    {
        return new AnalyticsEvent('user', 'login');
    }
}
