<?php

namespace OpenDominion\Contracts\Services\Analytics;

use OpenDominion\Contracts\Services\Analytics\AnalyticsEvent;
use RuntimeException;

interface AnalyticsService
{
    /**
     * Queues an Analytics flash event to be fired on the next request.
     *
     * @param AnalyticsEvent $event
     * @return void
     */
    public function queueFlashEvent(AnalyticsEvent $event): void;

    /**
     * Returns all flash events.
     *
     * @param bool $clear
     * @return AnalyticsEvent[]
     */
    public function getFlashEvents(bool $clear = true): array;
}
