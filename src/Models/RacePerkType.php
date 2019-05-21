<?php

namespace OpenDominion\Models;

class RacePerkType extends AbstractModel
{
    public function races()
    {
        return $this->belongsToMany(Race::class, 'race_perks', 'race_perk_type_id', 'race_id')->withTimestamps();
    }
}
