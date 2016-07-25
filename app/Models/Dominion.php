<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Model;

class Dominion extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $dates = ['created_at', 'updated_at'];

    public function race()
    {
        // todo: hasOne? belongsTo?
    }

    public function realm()
    {
        // todo: belonsTo?
    }

    public function round()
    {
        // todo: belongsTo?
    }

    public function user()
    {
        // todo: belongsTo?
    }
}
