<?php

namespace OpenDominion\Models\Dominion;

use Illuminate\Support\Str;
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
        'created_at' => 'datetime',
    ];

    protected $guarded = ['id', 'created_at'];

    const UPDATED_AT = null;

    public function getDateFormat()
    {
        return 'Y-m-d H:i:s.v';
    }

    public function getDeltaString()
    {
        $deltaValues = [];

        switch ($this->event) {
            case 'bank':
                foreach ($this->delta as $key => $value) {
                    if (Str::startsWith($key, 'resource_')) {
                        $prettyKey = Str::remove('resource_', $key);
                        array_push($deltaValues, "{$prettyKey}: {$value}");
                    }
                }
                break;
            case 'cast spell':
                foreach ($this->delta['queue']['active_spells'] as $key => $value) {
                    array_push($deltaValues, "{$key}: {$value}");
                }
                break;
            case 'change draft rate':
                foreach ($this->delta as $key => $value) {
                    if (Str::startsWith($key, 'draft_rate')) {
                        array_push($deltaValues, "{$key}: +{$value}");
                    }
                }
                break;
            case 'construct':
                foreach ($this->delta['queue']['construction'] as $key => $value) {
                    $prettyKey = Str::remove('building_', $key);
                    array_push($deltaValues, "{$prettyKey}: {$value}");
                }
                break;
            case 'daily bonus':
                foreach ($this->delta as $key => $value) {
                    if (Str::startsWith($key, 'daily_')) {
                        array_push($deltaValues, "{$key}");
                    }
                }
                break;
            case 'destroy':
                foreach ($this->delta as $key => $value) {
                    if (Str::startsWith($key, 'building_')) {
                        $prettyKey = Str::remove('building_', $key);
                        array_push($deltaValues, "{$prettyKey}: {$value}");
                    }
                }
                break;
            case 'explore':
                foreach ($this->delta['queue']['exploration'] as $key => $value) {
                    $prettyKey = Str::remove('land_', $key);
                    array_push($deltaValues, "{$prettyKey}: {$value}");
                }
                break;
            case 'improve':
                foreach ($this->delta as $key => $value) {
                    if (Str::startsWith($key, 'resource_')) {
                        $prettyKey = Str::remove('resource_', $key);
                        array_push($deltaValues, "{$prettyKey}: {$value}");
                    }
                    if (Str::startsWith($key, 'improvement_')) {
                        $prettyKey = Str::remove('improvement_', $key);
                        array_push($deltaValues, "{$prettyKey}: {$value}");
                    }
                }
                break;
            case 'release':
                foreach ($this->delta as $key => $value) {
                    if (Str::startsWith($key, 'military_')) {
                        $prettyKey = Str::remove('military_', $key);
                        array_push($deltaValues, "{$prettyKey}: {$value}");
                    }
                }
                break;
            case 'rezone':
                foreach ($this->delta as $key => $value) {
                    if (Str::startsWith($key, 'land_')) {
                        $prettyKey = Str::remove('land_', $key);
                        array_push($deltaValues, "{$prettyKey}: {$value}");
                    }
                }
                break;
            case 'tech':
                foreach ($this->delta as $key => $value) {
                    if (Str::startsWith($key, 'action')) {
                        array_push($deltaValues, "{$value}");
                    }
                }
                break;
            case 'train':
                foreach ($this->delta['queue']['training'] as $key => $value) {
                    $prettyKey = Str::remove('military_', $key);
                    array_push($deltaValues, "{$prettyKey}: {$value}");
                }
                break;
        }

        return implode(', ', $deltaValues);
    }
}
