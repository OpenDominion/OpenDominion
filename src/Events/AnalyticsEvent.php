<?php

namespace OpenDominion\Events;

use OpenDominion\Contracts\Services\AnalyticsService\Event as EventContract;

interface AnalyticsEvent
{
    public function getAnalyticsEvent(): EventContract;
}
