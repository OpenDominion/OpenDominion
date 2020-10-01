<?php

namespace OpenDominion\Models\MessageBoard;

use OpenDominion\Models\AbstractModel;
use OpenDominion\Models\User;

/**
 * OpenDominion\Models\MessageBoard\Category
 *
 * @property int $id
 * @property string $name
 * @property string $role_required
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\MessageBoard\Threads[] $threads
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\MessageBoard\Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\MessageBoard\Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\MessageBoard\Category query()
 * @mixin \Eloquent
 */
class Category extends AbstractModel
{
    protected $table = 'message_board_categories';

    public $timestamps = false;

    public function threads()
    {
        return $this->hasMany(Thread::class, 'message_board_category_id');
    }
}
