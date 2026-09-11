<?php

namespace OpenDominion\Services\Activity;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use OpenDominion\Models\Round;
use OpenDominion\Models\UserOrigin;
use OpenDominion\Models\UserOriginLookup;

class OriginLookupSelectionService
{
    public const TIER_SHARED = 'shared';
    public const TIER_TOP = 'top';
    public const TIER_FILLER = 'filler';

    public const TIERS = [self::TIER_SHARED, self::TIER_TOP, self::TIER_FILLER];

    public const TIER_LABELS = [
        self::TIER_SHARED => 'Shared IPs (used by 2+ dominions)',
        self::TIER_TOP => "Each dominion's most-used IP",
        self::TIER_FILLER => 'Other IPs per dominion, most distinct IPs first',
    ];

    /**
     * Selects the IP addresses in a round most worth spending IPQS lookups on.
     *
     * IPs that already hold lookup data are never selected, and each IP is
     * selected at most once. The requested tiers are filled in order until the limit is reached:
     *  1. shared: IPs used by two or more dominions, most dominions first
     *  2. top: each dominion's most-used IP, most active dominions first
     *  3. filler: each dominion's IPs after its most-used one, one per dominion per pass,
     *     starting with the dominions that use the most distinct IPs
     *
     * @param Round $round
     * @param int $limit
     * @param array<int, string> $tiers
     * @return Collection<int, array{ip_address: string, user_id: int, tier: string}>
     */
    public function selectForRound(Round $round, int $limit, array $tiers = self::TIERS): Collection
    {
        return $this->selectFromOrigins($this->getRoundOrigins($round), $this->getLookedUpIpAddresses($round), $limit, $tiers);
    }

    /**
     * Counts how many IP addresses each tier would select on its own, without a limit.
     *
     * @param Round $round
     * @return array<string, int>
     */
    public function countAvailableByTier(Round $round): array
    {
        $origins = $this->getRoundOrigins($round);
        $lookedUpIpAddresses = $this->getLookedUpIpAddresses($round);

        $counts = [];
        foreach (self::TIERS as $tier) {
            $counts[$tier] = $this->selectFromOrigins($origins, $lookedUpIpAddresses, PHP_INT_MAX, [$tier])->count();
        }

        return $counts;
    }

    /**
     * Applies the tier selection to a round's origins.
     *
     * @param Collection<int, UserOrigin> $origins
     * @param array<string, int> $lookedUpIpAddresses
     * @param int $limit
     * @param array<int, string> $tiers
     * @return Collection<int, array{ip_address: string, user_id: int, tier: string}>
     */
    protected function selectFromOrigins(Collection $origins, array $lookedUpIpAddresses, int $limit, array $tiers): Collection
    {
        $selection = [];

        if (in_array(self::TIER_SHARED, $tiers, true)) {
            $originsByIpAddress = $origins->groupBy('ip_address')
                ->filter(fn (Collection $ipOrigins) => $ipOrigins->unique('dominion_id')->count() > 1)
                ->sort(function (Collection $a, Collection $b) {
                    return [$b->unique('dominion_id')->count(), $b->sum('count'), $a->first()->ip_address]
                        <=> [$a->unique('dominion_id')->count(), $a->sum('count'), $b->first()->ip_address];
                });
            foreach ($originsByIpAddress as $ipOrigins) {
                $this->addToSelection($selection, $lookedUpIpAddresses, $limit, $ipOrigins->sortByDesc('count')->first(), self::TIER_SHARED);
            }
        }

        $originsByDominion = $origins->groupBy('dominion_id')
            ->map(fn (Collection $dominionOrigins) => $dominionOrigins->sort(function (UserOrigin $a, UserOrigin $b) {
                return [$b->count, $a->ip_address] <=> [$a->count, $b->ip_address];
            })->values());

        if (in_array(self::TIER_TOP, $tiers, true)) {
            $dominionsByActivity = $originsByDominion->sort(function (Collection $a, Collection $b) {
                return [$b->sum('count'), $a->first()->dominion_id] <=> [$a->sum('count'), $b->first()->dominion_id];
            });
            foreach ($dominionsByActivity as $dominionOrigins) {
                $this->addToSelection($selection, $lookedUpIpAddresses, $limit, $dominionOrigins->first(), self::TIER_TOP);
            }
        }

        $fillerQueues = [];
        if (in_array(self::TIER_FILLER, $tiers, true)) {
            $fillerQueues = $originsByDominion
                ->sort(function (Collection $a, Collection $b) {
                    return [$b->count(), $b->sum('count'), $a->first()->dominion_id]
                        <=> [$a->count(), $a->sum('count'), $b->first()->dominion_id];
                })
                ->map(fn (Collection $dominionOrigins) => $dominionOrigins->slice(1)->values()->all())
                ->filter()
                ->all();
        }

        while (!empty($fillerQueues) && count($selection) < $limit) {
            foreach (array_keys($fillerQueues) as $dominionId) {
                while (!empty($fillerQueues[$dominionId])) {
                    $origin = array_shift($fillerQueues[$dominionId]);
                    if ($this->addToSelection($selection, $lookedUpIpAddresses, $limit, $origin, self::TIER_FILLER)) {
                        break;
                    }
                }

                if (empty($fillerQueues[$dominionId])) {
                    unset($fillerQueues[$dominionId]);
                }
                if (count($selection) >= $limit) {
                    break;
                }
            }
        }

        return collect(array_values($selection));
    }

    /**
     * Counts the distinct IP addresses used in a round that have not been looked up yet.
     *
     * @param Round $round
     * @return int
     */
    public function countUnlookedIpAddresses(Round $round): int
    {
        $ipAddressCount = $this->getRoundOriginsQuery($round)->distinct()->count('ip_address');

        return $ipAddressCount - $this->getLookedUpIpAddressesQuery($round)->count();
    }

    /**
     * Adds an origin's IP address to the selection if it is still eligible.
     *
     * @param array<string, array{ip_address: string, user_id: int, tier: string}> $selection
     * @param array<string, int> $lookedUpIpAddresses
     * @param int $limit
     * @param UserOrigin $origin
     * @param string $tier
     * @return bool Whether the IP address was added
     */
    protected function addToSelection(array &$selection, array $lookedUpIpAddresses, int $limit, UserOrigin $origin, string $tier): bool
    {
        if (count($selection) >= $limit
            || isset($lookedUpIpAddresses[$origin->ip_address])
            || isset($selection[$origin->ip_address])
        ) {
            return false;
        }

        $selection[$origin->ip_address] = [
            'ip_address' => $origin->ip_address,
            'user_id' => $origin->user_id,
            'tier' => $tier,
        ];

        return true;
    }

    /**
     * Returns the origins recorded by the player dominions of a round.
     *
     * @param Round $round
     * @return Collection<int, UserOrigin>
     */
    protected function getRoundOrigins(Round $round): Collection
    {
        return $this->getRoundOriginsQuery($round)
            ->select(['user_id', 'dominion_id', 'ip_address', 'count'])
            ->get();
    }

    /**
     * Returns the round's IP addresses that already hold lookup data, as keys.
     *
     * @param Round $round
     * @return array<string, int>
     */
    protected function getLookedUpIpAddresses(Round $round): array
    {
        return $this->getLookedUpIpAddressesQuery($round)
            ->pluck('ip_address')
            ->flip()
            ->all();
    }

    /**
     * Returns a query for the origins recorded by the player dominions of a round.
     *
     * @param Round $round
     * @return Builder
     */
    protected function getRoundOriginsQuery(Round $round): Builder
    {
        return UserOrigin::query()
            ->whereIn('dominion_id', $round->dominions()->whereNotNull('dominions.user_id')->select('dominions.id'));
    }

    /**
     * Returns a query for the lookups of a round's IP addresses that already hold data.
     *
     * @param Round $round
     * @return Builder
     */
    protected function getLookedUpIpAddressesQuery(Round $round): Builder
    {
        return UserOriginLookup::query()
            ->whereIn('ip_address', $this->getRoundOriginsQuery($round)->select('ip_address'))
            ->whereNotNull('data');
    }
}
