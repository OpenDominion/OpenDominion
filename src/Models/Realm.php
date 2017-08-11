<?php

namespace OpenDominion\Models;

class Realm extends AbstractModel
{
    public function councilThreads()
    {
        return $this->hasMany(Council\Thread::class);
    }

    public function dominions()
    {
        return $this->hasMany(Dominion::class);
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
