<?php

namespace OpenDominion\Models\MessageBoard;

use Illuminate\Database\Eloquent\SoftDeletes;
use OpenDominion\Models\AbstractModel;
use OpenDominion\Models\User;

/**
 * OpenDominion\Models\MessageBoard\Post
 *
 * @property int $id
 * @property int $message_board_thread_id
 * @property int $user_id
 * @property string $body
 * @property bool $flagged_for_removal
 * @property array $flagged_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\User $user
 * @property-read \OpenDominion\Models\MessageBoard\Thread $thread
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\MessageBoard\Post newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\MessageBoard\Post newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\MessageBoard\Post query()
 * @mixin \Eloquent
 */
class Post extends AbstractModel
{
    use SoftDeletes;

    protected $table = 'message_board_posts';

    protected $casts = [
        'flagged_by' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function thread()
    {
        return $this->belongsTo(Thread::class, 'message_board_thread_id');
    }
}
