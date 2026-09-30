<?php

namespace OpenDominion\Services;

use Illuminate\Support\Collection;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundPerk;

class RoundPerkService
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_UPCOMING = 'upcoming';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_NOT_APPLICABLE = 'not_applicable';

    /**
     * @param Round $round
     * @param array<string, mixed> $attributes
     * @return RoundPerk
     */
    public function create(Round $round, array $attributes): RoundPerk
    {
        return $round->perks()->create($attributes);
    }

    /**
     * @param RoundPerk $perk
     * @param array<string, mixed> $attributes
     * @return RoundPerk
     */
    public function update(RoundPerk $perk, array $attributes): RoundPerk
    {
        $perk->update($attributes);

        return $perk;
    }

    public function delete(RoundPerk $perk): void
    {
        $perk->delete();
    }

    /**
     * All round perks with their status relative to the dominion, for display.
     *
     * @param Dominion $dominion
     * @return Collection<int, array{perk: RoundPerk, status: string}>
     */
    public function getPerksForDominion(Dominion $dominion): Collection
    {
        $day = $dominion->round->daysInRound();

        return $dominion->round->perks
            ->sortBy(['name', 'id'])
            ->map(function (RoundPerk $perk) use ($dominion, $day) {
                return [
                    'perk' => $perk,
                    'status' => $this->getStatus($perk, $dominion, $day),
                ];
            })
            ->values();
    }

    protected function getStatus(RoundPerk $perk, Dominion $dominion, int $day): string
    {
        if (!$perk->matchesAlignment($dominion)) {
            return static::STATUS_NOT_APPLICABLE;
        }

        if ($perk->from_day !== null && $day < $perk->from_day) {
            return static::STATUS_UPCOMING;
        }

        if ($perk->until_day !== null && $day > $perk->until_day) {
            return static::STATUS_EXPIRED;
        }

        return static::STATUS_ACTIVE;
    }
}
