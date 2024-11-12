<?php

namespace OpenDominion\Services\Activity;

use Jenssegers\Agent\Agent;
use OpenDominion\Models\User;
use OpenDominion\Models\UserActivity;
use OpenDominion\Models\UserIdentity;
use OpenDominion\Models\UserOrigin;
use OpenDominion\Models\UserOriginLookup;

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
    public function getDeviceString(): ?string
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

    /**
     * Records the identity of a user.
     *
     * @param User $user
     * @param string $fingerprint
     * @return void
     */
    public function recordIdentity(User $user, ?string $fingerprint, ?string $user_agent): void
    {
        if (!$fingerprint) {
            return;
        }

        $identity = UserIdentity::where([
            'user_id' => $user->id,
            'fingerprint' => $fingerprint
        ])->first();

        if ($identity) {
            $identity->increment('count');
        } else {
            UserIdentity::create([
                'user_id' => $user->id,
                'fingerprint' => $fingerprint,
                'user_agent' => $user_agent
            ]);
        }
    }

    /**
     * Records the origin of a user.
     *
     * @param User $user
     * @param int|null $dominion_id
     * @return void
     */
    public function recordOrigin(User $user, string $ip_address, int $dominion_id = null): void
    {
        if (!$ip_address || $ip_address == '127.0.0.1') {
            return;
        }

        $data = [
            'user_id' => $user->id,
            'ip_address' => $ip_address
        ];
        if ($dominion_id) {
            $data['dominion_id'] = $dominion_id;
        }

        $origin = UserOrigin::where($data)->first();

        if ($origin) {
            $origin->increment('count');
        } else {
            UserOriginLookup::firstOrCreate([
                'ip_address' => $ip_address
            ]);

            UserOrigin::create($data);
        }
    }
}
