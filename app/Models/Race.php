<?php

namespace OpenDominion\Models;

class Race extends AbstractModel
{
    public function perks()
    {
        return $this->hasMany(RacePerk::class);
    }

    public function units()
    {
        // todo
    }
}
