<?php

namespace OpenDominion\Events;

use OpenDominion\Contracts\Services\Analytics\AnalyticsEvent as AnalyticsEventContract;

interface HasAnalyticsEvent
{
    /**
     * Returns the AnalyticsEvent associated with this Event.
     *
     * @return AnalyticsEventContract
     */
    public function getAnalyticsEvent(): AnalyticsEventContract;
}
