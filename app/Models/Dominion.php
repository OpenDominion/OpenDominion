<?php namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Model;

class Dominion extends Model
{
    public function user()
    {
        return $this->belongsTo('OpenDominion\Models\User');
    }
}
