<?php

namespace OpenDominion\Events;

use OpenDominion\Services\Activity\ActivityEvent;
use OpenDominion\Services\Activity\HasActivityEvent;

class UserRegisteredEvent extends AbstractUserEvent implements HasActivityEvent
{
    /**
     * {@inheritdoc}
     */
    public function getActivityEvent(): ActivityEvent
    {
        return new ActivityEvent('user.register', ActivityEvent::STATUS_SUCCESS);
    }
}
