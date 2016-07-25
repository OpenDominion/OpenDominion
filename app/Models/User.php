<?php

namespace OpenDominion\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $hidden = ['password', 'remember_token', 'activation_code'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $dates = ['created_at', 'updated_at'];

    public function dominion(Round $round)
    {
//        return $this->dominions()->where('round_id', $round->id)->get();
    }

    public function dominions()
    {
        return $this->hasMany(Dominion::class);
    }
}
