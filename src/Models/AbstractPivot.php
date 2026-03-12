<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

abstract class AbstractPivot extends Pivot
{
    protected $guarded = ['created_at', 'updated_at'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
