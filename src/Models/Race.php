<?php

namespace OpenDominion\Models;

use Illuminate\Support\Collection;

class Race extends AbstractModel
{
    public function dominions()
    {
        return $this->hasMany(Dominion::class);
    }

    public function perks()
    {
        return $this->hasMany(RacePerk::class);
    }

    public function units()
    {
        return $this->hasMany(Unit::class)
            ->orderBy('slot');
    }

    /**
     * Gets a Race's perk multiplier.
     *
     * @param string $key
     * @return float
     */
    public function getPerkMultiplier(string $key): float
    {
        $perks = $this->perks->filter(function (RacePerk $racePerk) use ($key) {
            return ($racePerk->type->key === $key);
        });

        if ($perks->isEmpty()) {
            return 0;
        }

        return ((float)$perks->first()->value / 100);
    }

    /**
     * Try to get a unit perk value with provided key for a specific slot.
     *
     * @param int $slot
     * @param string|string[] $unitPerkTypes
     * @param mixed $default
     * @return int|int[]
     */
    public function getUnitPerkValueForUnitSlot(int $slot, $unitPerkTypes, $default = 0)
    {
        if (!is_array($unitPerkTypes)) {
            $unitPerkTypes = [$unitPerkTypes];
        }

        /** @var Collection|Unit[] $unitCollection */
        $unitCollection = $this->units->filter(function (Unit $unit) use ($slot, $unitPerkTypes) {
            return (
                ($unit->slot === $slot) &&
                ($unit->unit_perk_type_id !== null) &&
                in_array($unit->perkType->key, $unitPerkTypes, true)
            );
        });

        if ($unitCollection->isEmpty()) {
            return $default;
        }

        $perkValue = $unitCollection->first()->unit_perk_type_values;

        if (str_contains($perkValue, ',')) {
            $perkValue = explode(',', $perkValue);
        }

        return $perkValue;
    }
}
