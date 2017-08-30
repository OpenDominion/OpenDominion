<?php

namespace OpenDominion\Services\Activity;

use OpenDominion\Contracts\Services\Activity\ActivityEvent as ActivityEventContract;

interface HasActivityEvent
{
    /**
     * Returns the ActivityEvent associated with this event.
     *
     * @return ActivityEventContract
     */
    public function getActivityEvent(): ActivityEventContract;
}
