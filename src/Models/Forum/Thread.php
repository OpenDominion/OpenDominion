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
 * @property bool $flagged_for_removal
 * @property array $flagged_by
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

    protected $casts = [
        'flagged_by' => 'array',
    ];

    public function dominion()
    {
        return $this->belongsTo(Dominion::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'forum_thread_id');
    }

    public function latestPost()
    {
        return $this->hasOne(Post::class, 'forum_thread_id')->latest();
    }

    public function unflaggedPosts()
    {
        return $this->posts()->where('flagged_for_removal', false);
    }

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function getPostsCountAttribute($value)
    {
        return $value + 1;
    }
}
