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

    public function infoOps()
    {
        return $this->hasMany(InfoOp::class, 'source_realm_id');
    }

    public function infoOpTargetDominions()
    {
        return $this->hasManyThrough(
            Dominion::class,
            InfoOp::class,
            'source_realm_id',
            'id',
            null,
            'target_dominion_id'
        )
            ->groupBy('target_dominion_id')
            ->orderBy('updated_at', 'desc');
    }

    public function monarch()
    {
//        return $this->hasOne(Dominion::class, 'id', 'monarch_dominion_id');
    }

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function hasInfoOp(Dominion $targetDominion, string $infoOp)
    {
        //
    }
}
