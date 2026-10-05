<?php

namespace OpenDominion\Models;

class RoundTickRun extends AbstractModel
{
    protected $casts = [
        'tick_at' => 'datetime',
        'completed_at' => 'datetime',
        'attempts' => 'integer',
    ];
}
