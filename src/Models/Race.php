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
        return $this->hasMany(Unit::class);
    }
}
