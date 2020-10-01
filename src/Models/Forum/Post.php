<?php

namespace OpenDominion\Models\Forum;

use Illuminate\Database\Eloquent\SoftDeletes;
use OpenDominion\Models\AbstractModel;
use OpenDominion\Models\Dominion;

/**
 * OpenDominion\Models\Forum\Post
 *
 * @property int $id
 * @property int $forum_thread_id
 * @property int $dominion_id
 * @property string $body
 * @property bool $flagged_for_removal
 * @property array $flagged_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Dominion $dominion
 * @property-read \OpenDominion\Models\Forum\Thread $thread
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Forum\Post newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Forum\Post newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Forum\Post query()
 * @mixin \Eloquent
 */
class Post extends AbstractModel
{
    use SoftDeletes;

    protected $table = 'forum_posts';

    protected $casts = [
        'flagged_by' => 'array',
    ];

    public function dominion()
    {
        return $this->belongsTo(Dominion::class);
    }

    public function thread()
    {
        return $this->belongsTo(Thread::class, 'forum_thread_id');
    }

    public function save(array $options = [])
    {
        $saved = parent::save($options);

        if ($saved) {
            $this->thread()->update(['last_activity' => now()]);
        }

        return $saved;
    }
}
