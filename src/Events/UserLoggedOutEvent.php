<?php

namespace OpenDominion\Events;

use OpenDominion\Services\Activity\ActivityEvent;
use OpenDominion\Services\Activity\HasActivityEvent;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\HasAnalyticsEvent;

class UserLoggedOutEvent extends AbstractUserEvent implements HasActivityEvent, HasAnalyticsEvent
{
    /**
     * Returns the ActivityEvent associated with this event.
     *
     * @return ActivityEvent
     */
    public function getActivityEvent(): ActivityEvent
    {
        return new ActivityEvent('user.logout', ActivityEvent::STATUS_SUCCESS);
    }

    /**
     * Returns the AnalyticsEvent associated with this event.
     *
     * @return AnalyticsEvent
     */
    public function getAnalyticsEvent(): AnalyticsEvent
    {
        return new AnalyticsEvent('user', 'logout');
    }
}
