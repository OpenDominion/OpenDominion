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

    public function perks()
    {
        return $this->belongsToMany(UnitPerkType::class, 'unit_perks', 'unit_id', 'unit_perk_type_id')->withTimestamps()->withPivot('value');
    }

    public function race()
    {
        return $this->hasOne(Race::class);
    }
}
