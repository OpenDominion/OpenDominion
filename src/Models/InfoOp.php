<?php

namespace OpenDominion\Models;

class InfoOp extends AbstractModel
{
    protected $casts = [
        'data' => 'array',
    ];

    protected function sourceRealm()
    {
//        return $this->belongsTo(Realm::class);
    }

    protected function sourceDominion()
    {
        //
    }

    protected function targetDominion()
    {
        //
    }
}
