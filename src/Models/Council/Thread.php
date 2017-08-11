<?php

namespace OpenDominion\Models\Council;

use OpenDominion\Models\AbstractModel;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;

class Thread extends AbstractModel
{
    protected $table = 'council_threads';

    public function dominion()
    {
        return $this->belongsTo(Dominion::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'council_thread_id');
    }

    public function realm()
    {
        return $this->belongsTo(Realm::class);
    }
}
