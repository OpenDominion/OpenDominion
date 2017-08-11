<?php

namespace OpenDominion\Models\Council;

use OpenDominion\Models\AbstractModel;
use OpenDominion\Models\Dominion;

class Post extends AbstractModel
{
    protected $table = 'council_posts';

    public function dominion()
    {
        return $this->belongsTo(Dominion::class);
    }

    public function thread()
    {
        return $this->belongsTo(Thread::class);
    }
}
