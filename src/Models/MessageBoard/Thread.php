<?php

namespace OpenDominion\Models\MessageBoard;

use Illuminate\Database\Eloquent\SoftDeletes;
use OpenDominion\Models\AbstractModel;
use OpenDominion\Models\User;

/**
 * OpenDominion\Models\MessageBoard\Thread
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $body
 * @property bool $flagged_for_removal
 * @property array $flagged_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\MessageBoard\Post[] $posts
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\MessageBoard\Thread newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\MessageBoard\Thread newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\MessageBoard\Thread query()
 * @mixin \Eloquent
 */
class Thread extends AbstractModel
{
    use SoftDeletes;

    protected $table = 'message_board_threads';

    protected $casts = [
        'flagged_by' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'message_board_category_id');
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'message_board_thread_id');
    }

    public function latestPost()
    {
        return $this->hasOne(Post::class, 'message_board_thread_id')->latest();
    }

    public function latestPosts()
    {
        return $this->hasMany(Post::class, 'message_board_thread_id')->orderByDesc('created_at')->take(5);
    }

    public function unflaggedPosts()
    {
        return $this->posts()->where('flagged_for_removal', false);
    }

    public function getPostsCountAttribute($value)
    {
        return $value + 1;
    }
}
