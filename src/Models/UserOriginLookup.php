<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\UserOrigin
 *
 * @property int $id
 * @property string $ip_address
 * @property string|null $isp
 * @property string|null $organization
 * @property string|null $country
 * @property string|null $region
 * @property string|null $city
 * @property bool|null $vpn
 * @property float|null $score
 * @property array|null $data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\UserOrigin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\UserOrigin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\UserOrigin query()
 * @mixin \Eloquent
 */
class UserOriginLookup extends AbstractModel
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
