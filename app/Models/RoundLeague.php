<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Model;

class RoundLeague extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function rounds()
    {
        return $this->hasMany(Round::class);
    }
}