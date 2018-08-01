<?php

namespace OpenDominion\Models;

class RacePerk extends AbstractModel
{
    protected $casts = [
        'value' => 'float',
    ];

    public function race()
    {
        return $this->belongsTo(Race::class);
    }

    public function type()
    {
        return $this->belongsTo(RacePerkType::class, 'race_perk_type_id');
    }
}
