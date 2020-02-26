<?php

namespace OpenDominion\Models\Forum;

use Illuminate\Database\Eloquent\SoftDeletes;
use OpenDominion\Models\AbstractModel;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;

/**
 * OpenDominion\Models\Form\Thread
 *
 * @property int $id
 * @property int $round_id
 * @property int $dominion_id
 * @property string $title
 * @property string $body
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Dominion $dominion
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Forum\Post[] $posts
 * @property-read \OpenDominion\Models\Round $round
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Forum\Thread newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Forum\Thread newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Forum\Thread query()
 * @mixin \Eloquent
 */
class Thread extends AbstractModel
{
    use SoftDeletes;

    protected $table = 'forum_threads';

    public function dominion()
    {
        return $this->belongsTo(Dominion::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'forum_thread_id');
    }

    public function round()
    {
        return $this->belongsTo(Round::class);
    }
}
