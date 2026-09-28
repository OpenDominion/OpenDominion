<?php

namespace OpenDominion\Services\Activity;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;
use OpenDominion\Models\User;
use OpenDominion\Models\UserActivity;
use OpenDominion\Models\UserIdentity;
use OpenDominion\Models\UserOrigin;
use OpenDominion\Models\UserOriginLookup;

class ActivityService
{
    /**
     * IPQS response keys mapped to the user_origin_lookups columns they populate.
     */
    private const LOOKUP_COLUMNS = [
        'ISP' => 'isp',
        'organization' => 'organization',
        'country_code' => 'country',
        'region' => 'region',
        'city' => 'city',
        'proxy' => 'proxy',
        'vpn' => 'vpn',
        'tor' => 'tor',
        'active_vpn' => 'active_vpn',
        'active_tor' => 'active_tor',
        'fraud_score' => 'score',
    ];

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
     * @param string|null $fingerprint
     * @param string|null $user_agent
     * @return void
     */
    public function recordIdentity(User $user, string|null $fingerprint, string|null $user_agent): void
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
     * @param string|null $ip_address
     * @param int|null $dominion_id
     * @return void
     */
    public function recordOrigin(User $user, string|null $ip_address, int|null $dominion_id = null): void
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

    /**
     * Performs a user origin lookup against IPQS.
     *
     * Lookup rows that already hold data are not requested again. A failed
     * request (including an IPQS response with success=false, such as an
     * invalid key or exhausted quota) leaves the row untouched so it can be retried.
     *
     * @param User $user
     * @param string|null $ip_address
     * @return bool Whether the lookup row holds IPQS data afterwards
     */
    public function performLookup(User $user, string|null $ip_address): bool
    {
        if (!$ip_address || $ip_address == '127.0.0.1') {
            return false;
        }

        $origin = UserOriginLookup::where('ip_address', $ip_address)->first();
        if (!$origin) {
            return false;
        }
        if ($origin->data !== null) {
            return true;
        }

        $key = config('app.ipqs_api_key');
        if (!$key) {
            return false;
        }

        try {
            $lookupResponse = Http::withoutVerifying()
                ->timeout(5)
                ->get("https://www.ipqualityscore.com/api/json/ip/{$key}/{$ip_address}", [
                    'userID' => $user->id,
                    'strictness' => 1,
                    'allow_public_access_points' => true
                ]);
        } catch (ConnectionException $e) {
            Log::warning('IPQS lookup connection failed', [
                'ip_address' => $ip_address,
                'error' => str_replace($key, '[redacted]', $e->getMessage()),
            ]);
            return false;
        }

        $result = $lookupResponse->json();
        if (!$lookupResponse->successful() || !is_array($result) || empty($result['success'])) {
            Log::warning('IPQS lookup failed', [
                'ip_address' => $ip_address,
                'status' => $lookupResponse->status(),
                'message' => is_array($result) ? ($result['message'] ?? null) : null,
            ]);
            return false;
        }

        foreach (self::LOOKUP_COLUMNS as $responseKey => $column) {
            if (isset($result[$responseKey])) {
                $origin->$column = $result[$responseKey];
            }
        }
        $origin->data = $result;
        $origin->save();

        return true;
    }
}
