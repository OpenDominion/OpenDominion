<?php

namespace OpenDominion\Services\Analytics;

class AnalyticsService
{
    const SESSION_NAME_FLASH = 'analyticsservice_flash_events';

    /**
     * Queues an Analytics flash event to be fired on the next request.
     *
     * @param AnalyticsEvent $event
     * @return void
     */
    public function queueFlashEvent(AnalyticsEvent $event): void
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
     * @param bool $clear
     * @return AnalyticsEvent[]
     */
    public function getFlashEvents(bool $clear = true): array
    {
        $events = request()->session()->get(static::SESSION_NAME_FLASH, []);

        if ($clear) {
            request()->session()->flash(static::SESSION_NAME_FLASH, []);
        }

        return $events;
    }
}
