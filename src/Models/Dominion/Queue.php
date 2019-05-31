<?php

namespace OpenDominion\Models\Dominion;

use OpenDominion\Models\AbstractModel;

/**
 * OpenDominion\Models\Dominion\Queue
 *
 * @property int $id
 * @property int $dominion_id
 * @property string $source
 * @property string $resource
 * @property int $hours
 * @property int $amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Dominion\History newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Dominion\History newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Dominion\History query()
 * @mixin \Eloquent
 */
class Queue extends AbstractModel
{
    protected $table = 'dominion_queue';

    protected $guarded = ['id', 'created_at'];

    protected $dates = ['created_at'];

    const UPDATED_AT = null;
}
