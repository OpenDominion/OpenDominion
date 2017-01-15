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
        return $this->hasMany(Unit::class)->orderBy('slot')->limit(4);
    }

    /**
     * Gets a Race's perk multiplier.
     *
     * @param string $key
     * @return float
     */
    public function getPerkMultiplier($key)
    {
        $perk = $this->perks()->with('type')->whereHas('type', function ($q) use ($key) {
            $q->where('key', $key);
        })->first();

        if ($perk === null) {
            return (float)0;
        }

        return (float)($perk->value / 100);
    }
}
