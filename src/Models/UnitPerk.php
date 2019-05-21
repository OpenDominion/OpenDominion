<?php

namespace OpenDominion\Models;

class UnitPerk extends AbstractModel
{
    protected $casts = [
        'value' => 'float',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function type()
    {
        return $this->belongsTo(UnitPerkType::class, 'unit_perk_type_id');
    }
}
