<?php

namespace OpenDominion\Models\Dominion;

use OpenDominion\Models\AbstractModel;

class History extends AbstractModel
{
    protected $table = 'dominion_history';

    protected $casts = [
        'delta' => 'array',
    ];

    protected $guarded = ['id', 'created_at'];

    protected $dates = ['created_at'];

    const UPDATED_AT = null;
}
