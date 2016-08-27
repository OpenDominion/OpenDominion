<?php

namespace OpenDominion\Models;

class Realm extends AbstractModel
{
    public function dominions()
    {
        // todo: hasMany
    }

    public function monarch()
    {
//        return $this->hasOne(Dominion::class, 'id', 'monarch_dominion_id');
    }

    public function round()
    {
        return $this->belongsTo(Round::class);
    }
}
