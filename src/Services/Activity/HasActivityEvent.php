<?php

namespace OpenDominion\Services\Activity;

use OpenDominion\Models\User;

interface HasActivityEvent
{
    /**
     * Returns the ActivityEvent associated with this event.
     *
     * @return ActivityEvent
     */
    public function getActivityEvent(): ActivityEvent;

    /**
     * Returns the User associated with this event.
     *
     * @return User
     */
    public function getUser(): User;
}
