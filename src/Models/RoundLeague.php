<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\RoundLeague
 *
 * @property int $id
 * @property string $key
 * @property string $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Round[] $rounds
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\RoundLeague newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\RoundLeague newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\RoundLeague query()
 * @mixin \Eloquent
 */
class RoundLeague extends AbstractModel
{
    public function rounds()
    {
        return $this->hasMany(Round::class);
    }
}
