<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Model;

class RacePerkType extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $dates = ['created_at', 'updated_at'];

    public function perks()
    {
        return $this->hasMany(RacePerk::class);
    }

    public function races()
    {
        return $this->hasManyThrough(Race::class, RacePerk::class, null, 'id');
    }
}
