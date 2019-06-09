<?php

namespace OpenDominion\Models\Council;

use OpenDominion\Models\AbstractModel;
use OpenDominion\Models\Dominion;

/**
 * OpenDominion\Models\Council\Post
 *
 * @property int $id
 * @property int $council_thread_id
 * @property int $dominion_id
 * @property string $body
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Dominion $dominion
 * @property-read \OpenDominion\Models\Council\Thread $thread
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Council\Post newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Council\Post newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Council\Post query()
 * @mixin \Eloquent
 */
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
