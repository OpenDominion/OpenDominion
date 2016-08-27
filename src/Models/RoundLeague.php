<?php

namespace OpenDominion\Models;

class RoundLeague extends AbstractModel
{
    public function rounds()
    {
        return $this->hasMany(Round::class);
    }
}
