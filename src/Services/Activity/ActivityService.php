<?php

namespace OpenDominion\Services\Activity;

use Jenssegers\Agent\Agent;
use OpenDominion\Contracts\Services\Activity\ActivityEvent;
use OpenDominion\Contracts\Services\Activity\ActivityService as ActivityServiceContract;
use OpenDominion\Models\User;
use OpenDominion\Models\UserActivity;

class ActivityService implements ActivityServiceContract
{
    /**
     * {@inheritdoc}
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

    protected function getDeviceString()
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
