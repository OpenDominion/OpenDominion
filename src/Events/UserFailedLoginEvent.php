<?php

namespace OpenDominion\Events;

use OpenDominion\Contracts\Services\Activity\ActivityEvent as ActivityEventContract;
use OpenDominion\Services\Activity\ActivityEvent;
use OpenDominion\Services\Activity\HasActivityEvent;

class UserFailedLoginEvent extends AbstractUserEvent implements HasActivityEvent
{
    /**
     * {@inheritdoc}
     */
    public function getActivityEvent(): ActivityEventContract
    {
        // todo: ioc
        return new ActivityEvent('user.login.failed', ActivityEventContract::STATUS_WARNING);
    }
}
