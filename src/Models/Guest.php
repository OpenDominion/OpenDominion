<?php

namespace OpenDominion\Models;

use Pseudo\Contracts\GuestContract;

class Guest extends User implements GuestContract
{
    public function getDisplayNameAttribute()
    {
        return 'Anonymous';
    }
}
