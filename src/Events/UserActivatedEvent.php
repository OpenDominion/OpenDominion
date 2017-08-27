<?php

namespace OpenDominion\Events;

use OpenDominion\Contracts\Services\Analytics\AnalyticsEvent as AnalyticsEventContract;
use OpenDominion\Services\Analytics\AnalyticsEvent;

class UserActivatedEvent extends AbstractUserEvent implements HasAnalyticsEvent
{
    /**
     * {@inheritdoc}
     */
    public function getAnalyticsEvent(): AnalyticsEventContract
    {
        return new AnalyticsEvent('user', 'activate');
    }
}
