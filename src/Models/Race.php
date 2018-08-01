<?php

namespace OpenDominion\Models;

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
            ->orderBy('slot')
            ->limit(4);
    }

    /**
     * Gets a Race's perk multiplier.
     *
     * @param string $key
     * @return float
     */
    public function getPerkMultiplier($key): float
    {
        $perks = $this->perks->filter(function (RacePerk $racePerk) use ($key) {
            return ($racePerk->type->key === $key);
        });

        if ($perks->isEmpty()) {
            return (float)0;
        }

        return ((float)$perks->first()->value / 100);
    }
}
