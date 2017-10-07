<?php

namespace OpenDominion\Events;

use OpenDominion\Services\Activity\ActivityEvent;
use OpenDominion\Services\Activity\HasActivityEvent;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\HasAnalyticsEvent;

class UserActivatedEvent extends AbstractUserEvent implements HasActivityEvent, HasAnalyticsEvent
{
    /**
     * {@inheritdoc}
     */
    public function getActivityEvent(): ActivityEvent
    {
        return new ActivityEvent('user.activate', ActivityEvent::STATUS_SUCCESS);
    }

    /**
     * {@inheritdoc}
     */
    public function getAnalyticsEvent(): AnalyticsEvent
    {
        return new AnalyticsEvent('user', 'activate');
    }
}
