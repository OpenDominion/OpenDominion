<?php

namespace OpenDominion\Services\Analytics;

use OpenDominion\Contracts\Services\Analytics\AnalyticsEvent;
use OpenDominion\Contracts\Services\Analytics\AnalyticsService as AnalyticsServiceContract;

class AnalyticsService implements AnalyticsServiceContract
{
    const SESSION_NAME_FLASH = 'analyticsservice_flash_events';

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
