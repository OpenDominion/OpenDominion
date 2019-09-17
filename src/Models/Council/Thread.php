<?php

namespace OpenDominion\Models\Council;

use Illuminate\Database\Eloquent\SoftDeletes;
use OpenDominion\Models\AbstractModel;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;

/**
 * OpenDominion\Models\Council\Thread
 *
 * @property int $id
 * @property int $realm_id
 * @property int $dominion_id
 * @property string $title
 * @property string $body
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Dominion $dominion
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Council\Post[] $posts
 * @property-read \OpenDominion\Models\Realm $realm
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Council\Thread newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Council\Thread newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Council\Thread query()
 * @mixin \Eloquent
 */
class Thread extends AbstractModel
{
    use SoftDeletes;

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
