<?php

namespace OpenDominion\Models;

class Unit extends AbstractModel
{
    public function perkType()
    {
        return $this->hasOne(UnitPerkType::class, 'id', 'unit_perk_type_id');
    }

    public function race()
    {
        return $this->hasOne(Race::class);
    }
}
