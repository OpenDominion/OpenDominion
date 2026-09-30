<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\RoundPerk
 *
 * @property int $id
 * @property int $round_id
 * @property string $key
 * @property string $value
 * @property string|null $alignment
 * @property int|null $from_day
 * @property int|null $until_day
 * @property string|null $name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Round $round
 */
class RoundPerk extends AbstractModel
{
    protected $table = 'round_perks';

    protected $casts = [
        'from_day' => 'integer',
        'until_day' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    /**
     * Whether the perk is in effect on the given day of the round (inclusive bounds).
     *
     * @param int $day
     * @return bool
     */
    public function isActiveOnDay(int $day): bool
    {
        if ($this->from_day !== null && $day < $this->from_day) {
            return false;
        }

        if ($this->until_day !== null && $day > $this->until_day) {
            return false;
        }

        return true;
    }

    /**
     * Whether the perk applies to the dominion's race alignment.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function matchesAlignment(Dominion $dominion): bool
    {
        return ($this->alignment === null || $this->alignment === $dominion->race->alignment);
    }

    /**
     * Whether the perk currently applies to the dominion.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function appliesTo(Dominion $dominion): bool
    {
        return $this->matchesAlignment($dominion) && $this->isActiveOnDay($dominion->round->daysInRound());
    }

    /**
     * Returns the comma-delimited parts of a compound value.
     *
     * @return string[]
     */
    public function getValueParts(): array
    {
        return array_map('trim', explode(',', $this->value));
    }
}
