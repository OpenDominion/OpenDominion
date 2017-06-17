<?php

namespace OpenDominion\Services\User;

use OpenDominion\Models\User;
use OpenDominion\Models\UserActivity;

class ActivityService
{
    /**
     * Records a user activity.
     *
     * @param User $user
     * @param string $activity
     * @param array $context
     */
    public function recordActivity(User $user, $activity, array $context = [])
    {
        $user->activities()->save(new UserActivity([
            'ip' => request()->ip(),
            'activity' => $activity,
            'context' => (!empty($context) ? $context : null),
        ]));
    }
}
