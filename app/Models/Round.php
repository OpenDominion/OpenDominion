<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Model;

class Round extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $dates = ['start_date', 'end_date', 'created_at', 'updated_at'];

    public function league()
    {
        return $this->hasOne(RoundLeague::class, 'id', 'round_league_id');
    }

    public function realms()
    {
        return $this->hasMany(Realm::class);
    }
}
