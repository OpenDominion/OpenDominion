<?php

namespace OpenDominion\Models\Dominion;

use OpenDominion\Models\AbstractModel;

/**
 * OpenDominion\Models\Dominion\History
 *
 * @property int $id
 * @property int $dominion_id
 * @property string $event
 * @property array $delta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Dominion\History newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Dominion\History newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Dominion\History query()
 * @mixin \Eloquent
 */
class History extends AbstractModel
{
    protected $table = 'dominion_history';

    protected $casts = [
        'delta' => 'array',
    ];

    protected $guarded = ['id', 'created_at'];

    protected $dates = ['created_at'];

    const UPDATED_AT = null;
}
