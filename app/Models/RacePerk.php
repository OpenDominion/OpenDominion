<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Model;

class RacePerk extends Model
{
    public function race()
    {
        return $this->belongsTo(Race::class);
    }

    public function type()
    {
        return $this->belongsTo(RacePerkType::class, 'race_perk_type_id');
    }

}