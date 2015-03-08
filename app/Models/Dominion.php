<?php namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Model;

class Dominion extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function user()
    {
        return $this->belongsTo('OpenDominion\Models\User');
    }
}
