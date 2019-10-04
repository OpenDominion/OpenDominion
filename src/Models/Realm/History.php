<?php

namespace OpenDominion\Models\Realm;

use OpenDominion\Models\AbstractModel;

/**
 * OpenDominion\Models\Realm\History
 *
 * @property int $id
 * @property int $dominion_id
 * @property string $event
 * @property array $delta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Realm\History newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Realm\History newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Realm\History query()
 * @mixin \Eloquent
 */
class History extends AbstractModel
{
    protected $table = 'realm_history';

    protected $casts = [
        'delta' => 'array',
    ];

    protected $guarded = ['id', 'created_at'];

    protected $dates = ['created_at'];

    const UPDATED_AT = null;
}
