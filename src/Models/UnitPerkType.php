<?php

namespace OpenDominion\Models;

class UnitPerkType extends AbstractModel
{
    public function units()
    {
        return $this->belongsToMany(Unit::class, 'unit_perks', 'unit_perk_type_id', 'unit_id')->withTimestamps();
    }
}
