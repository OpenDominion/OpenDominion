<?php

namespace OpenDominion\Contracts\Services\Activity;

use OpenDominion\Models\User;

interface ActivityService
{
    /**
     * Records an activity event for a user.
     *
     * @param User $user
     * @param ActivityEvent $activityEvent
     * @return void
     */
    public function recordActivity(User $user, ActivityEvent $activityEvent): void;
}
