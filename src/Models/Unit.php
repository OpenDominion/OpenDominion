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

    public function getNetworth()
    {
        if (in_array($this->slot, [1, 2])) {
            return 5;
        }

        return (
            (1.8 * min(6, max($this->power_offense, $this->power_defense)))
            + (0.45 * min(6, min($this->power_offense, $this->power_defense)))
            + (0.2 * (max(($this->power_offense - 6), 0) + max(($this->power_defense - 6), 0)))
        );
    }
}
