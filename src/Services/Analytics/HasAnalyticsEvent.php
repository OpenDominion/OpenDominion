<?php

namespace OpenDominion\Services\Analytics;

interface HasAnalyticsEvent
{
    /**
     * Returns the AnalyticsEvent associated with this event.
     *
     * @return AnalyticsEvent
     */
    public function getAnalyticsEvent(): AnalyticsEvent;
}
