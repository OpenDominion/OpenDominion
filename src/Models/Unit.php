<?php

namespace OpenDominion\Models;

class Unit extends AbstractModel
{
    protected $casts = [
        'slot' => 'integer',
        'cost_platinum' => 'integer',
        'cost_ore' => 'integer',
        'power_offense' => 'float',
        'power_defense' => 'float',
        'need_boat' => 'boolean',
    ];

    public function perkType()
    {
        return $this->hasOne(UnitPerkType::class, 'id', 'unit_perk_type_id');
    }

    public function race()
    {
        return $this->hasOne(Race::class);
    }
}
