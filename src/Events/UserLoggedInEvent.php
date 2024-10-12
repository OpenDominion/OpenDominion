<?php

namespace OpenDominion\Events;

use OpenDominion\Services\Activity\ActivityEvent;
use OpenDominion\Services\Activity\HasActivityEvent;

class UserLoggedInEvent extends AbstractUserEvent implements HasActivityEvent
{
    /**
     * {@inheritdoc}
     */
    public function getActivityEvent(): ActivityEvent
    {
        return new ActivityEvent('user.login', ActivityEvent::STATUS_SUCCESS);
    }
}
