<?php

namespace OpenDominion\Events;

use OpenDominion\Services\Activity\ActivityEvent;
use OpenDominion\Services\Activity\HasActivityEvent;

class UserLoggedOutEvent extends AbstractUserEvent implements HasActivityEvent
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
}
