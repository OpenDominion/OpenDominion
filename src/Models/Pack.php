<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Builder;

class Pack extends AbstractModel
{
    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dominions()
    {
        return $this->hasMany(Dominion::class);
    }
}