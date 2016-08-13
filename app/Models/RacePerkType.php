<?php

namespace OpenDominion\Models;

class RacePerkType extends AbstractModel
{
    public function perks()
    {
        return $this->hasMany(RacePerk::class);
    }

    public function races()
    {
        return $this->hasManyThrough(Race::class, RacePerk::class, null, 'id');
    }
}
