<?php

namespace OpenDominion\Events;

use OpenDominion\Contracts\Services\Activity\ActivityEvent as ActivityEventContract;
use OpenDominion\Contracts\Services\Analytics\AnalyticsEvent as AnalyticsEventContract;
use OpenDominion\Services\Activity\ActivityEvent;
use OpenDominion\Services\Activity\HasActivityEvent;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\HasAnalyticsEvent;

class UserActivatedEvent extends AbstractUserEvent implements HasActivityEvent, HasAnalyticsEvent
{
    /**
     * {@inheritdoc}
     */
    public function getActivityEvent(): ActivityEventContract
    {
        // todo: ioc
        return new ActivityEvent('user.activate', ActivityEventContract::STATUS_SUCCESS);
    }

    /**
     * {@inheritdoc}
     */
    public function getAnalyticsEvent(): AnalyticsEventContract
    {
        // todo: ioc
        return new AnalyticsEvent('user', 'activate');
    }
}
