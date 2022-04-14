<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\Hero
 *
 * @property int $id
 * @property string $name
 * @property string $class
 * @property string $vocation
 * @property int $experience
 * @property int $level
 * @property \Illuminate\Support\Carbon|null $returning_at
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Hero newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Hero newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Hero query()
 * @mixin \Eloquent
 */
class Hero extends AbstractModel
{
    protected $dates = ['returning_at', 'created_at', 'updated_at'];

    public function dominion()
    {
        return $this->hasOne(Dominion::class);
    }
}
