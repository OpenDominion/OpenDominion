<?php

namespace OpenDominion\Services\Activity;

use OpenDominion\Contracts\Services\Activity\ActivityEvent as ActivityEventContract;
use OpenDominion\Models\User;

interface HasActivityEvent
{
    /**
     * Returns the ActivityEvent associated with this event.
     *
     * @return ActivityEventContract
     */
    public function getActivityEvent(): ActivityEventContract;

    /**
     * Returns the User associated with this event.
     *
     * @return User
     */
    public function getUser(): User;
}
