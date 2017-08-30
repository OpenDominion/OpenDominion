<?php

namespace OpenDominion\Services\Analytics;

use OpenDominion\Contracts\Services\Analytics\AnalyticsEvent as AnalyticsEventContract;

interface HasAnalyticsEvent
{
    /**
     * Returns the AnalyticsEvent associated with this event.
     *
     * @return AnalyticsEventContract
     */
    public function getAnalyticsEvent(): AnalyticsEventContract;
}
