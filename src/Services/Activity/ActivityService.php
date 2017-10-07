<?php

namespace OpenDominion\Services\Activity;

use Jenssegers\Agent\Agent;
use OpenDominion\Models\User;
use OpenDominion\Models\UserActivity;

class ActivityService
{
    /**
     * Records an activity event for a user.
     *
     * @param User $user
     * @param ActivityEvent $activityEvent
     * @return void
     */
    public function recordActivity(User $user, ActivityEvent $activityEvent): void
    {
        $user->activities()->save(new UserActivity([
            'ip' => request()->ip(),
            'device' => $this->getDeviceString(),
            'key' => $activityEvent->getKey(),
            'status' => $activityEvent->getStatus(),
            'context' => (!empty($activityEvent->getContext()) ? $activityEvent->getContext() : null),
        ]));
    }

    /**
     * Returns a friendly user device string.
     *
     * @return string|null
     */
    protected function getDeviceString(): ?string
    {
        $userAgent = request()->userAgent();

        $deviceString = null;

        if ($userAgent === 'Symfony/3.X') {
            $deviceString = 'Unknown';

        } else {
            $agent = new Agent;
            $agent->setUserAgent($userAgent);

            $browser = $agent->browser();

            if ($agent->isDesktop()) {
                $platform = $agent->platform();
                $deviceString = sprintf(
                    '%s %s on %s %s',
                    $browser,
                    $agent->version($browser),
                    $agent->platform(),
                    $agent->version($platform)
                );
            } else {
                $deviceString = sprintf(
                    '%s %s on %s',
                    $browser,
                    $agent->version($browser),
                    $agent->device()
                );
            }
        }

        return $deviceString;
    }
}
