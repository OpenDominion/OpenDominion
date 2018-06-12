<?php

namespace OpenDominion\Models;

use Carbon\Carbon;

class InfoOp extends AbstractModel
{
    protected $casts = [
        'source_realm_id' => 'int',
        'source_dominion_id' => 'int',
        'target_dominion_id' => 'int',
        'data' => 'array',
    ];

    public function sourceRealm()
    {
//        return $this->belongsTo(Realm::class);
    }

    public function sourceDominion()
    {
        return $this->belongsTo(Dominion::class, 'source_dominion_id');
    }

    public function targetDominion()
    {
        //
    }

    public function isStale(): bool
    {
        return $this->updated_at < new Carbon('last hour');
    }
}
