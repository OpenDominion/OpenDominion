<?php

namespace OpenDominion\Services;

use OpenDominion\Services\AnalyticsService\Event;
use RuntimeException;

class AnalyticsService
{
    const SESSION_NAME_FLASH = 'analyticsservice_flash_events';

    /**
     * Queues an Analytics flash event to be fired on the next request.
     *
     * @param Event $event
     * @throws RuntimeException
     */
    public function queueFlashEvent(Event $event)
    {
        $events = $this->getFlashEvents();
        $events[] = $event;

        request()->session()->flash(
            static::SESSION_NAME_FLASH,
            $events
        );
    }

    /**
     * Returns all flash events.
     *
     * @return Event[]
     * @throws RuntimeException
     */
    public function getFlashEvents()
    {
        return request()->session()->get(static::SESSION_NAME_FLASH, []);
    }
}
