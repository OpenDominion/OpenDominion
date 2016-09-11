<?php

namespace OpenDominion\Models;

class UnitPerkType extends AbstractModel
{
    public function units()
    {
        return $this->hasMany(Unit::class);
    }
}
