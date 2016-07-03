<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Model;

class Realm extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function monarch()
    {
//        return $this->hasOne(Dominion::class, 'id', 'monarch_dominion_id');
    }

    public function round()
    {
        return $this->belongsTo(Round::class);
    }
}