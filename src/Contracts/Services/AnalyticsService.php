<?php

namespace OpenDominion\Contracts\Services;

use OpenDominion\Contracts\Services\AnalyticsService\Event;
use RuntimeException;

interface AnalyticsService
{
    /**
     * Queues an Analytics flash event to be fired on the next request.
     *
     * @param Event $event
     * @throws RuntimeException
     */
    public function queueFlashEvent(Event $event);

    /**
     * Returns all flash events.
     *
     * @return Event[]
     * @throws RuntimeException
     */
    public function getFlashEvents();
}
