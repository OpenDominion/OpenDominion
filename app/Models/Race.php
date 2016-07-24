<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Model;

class Race extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $dates = ['created_at', 'updated_at'];

    public function perks()
    {
        return $this->hasMany(RacePerk::class);
    }

    public function units()
    {
        // todo
    }
}
