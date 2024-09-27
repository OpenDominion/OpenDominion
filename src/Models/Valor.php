<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\Valor
 *
 * @property int $id
 * @property int $round_id
 * @property int $race_id
 * @property int $dominion_id
 * @property string $source
 * @property float $amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Dominion $dominion
 * @property-read \OpenDominion\Models\Race $race
 * @property-read \OpenDominion\Models\Round $round
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Valor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Valor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Valor query()
 * @mixin \Eloquent
 */
class Valor extends AbstractModel
{
    protected $table = 'valor';

    public function dominion()
    {
        return $this->hasOne(Dominion::class);
    }

    public function realm()
    {
        return $this->hasOne(Realm::class);
    }

    public function round()
    {
        return $this->hasOne(Round::class);
    }
}
