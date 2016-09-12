<?php

namespace OpenDominion\Models;

class Unit extends AbstractModel
{
    public function perkType()
    {
        return $this->hasOne(UnitPerkType::class);
    }

    public function race()
    {
        return $this->hasOne(Race::class);
    }
}
