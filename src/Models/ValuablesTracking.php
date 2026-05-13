<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\ValuablesTracking
 *
 * @property int $id
 * @property int $round_id
 * @property int $source_dominion_id
 * @property int $target_dominion_id
 * @property int $progress
 * @property \Illuminate\Support\Carbon|null $last_discovered_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Round $round
 * @property-read \OpenDominion\Models\Dominion $sourceDominion
 * @property-read \OpenDominion\Models\Dominion $targetDominion
 */
class ValuablesTracking extends AbstractModel
{
    protected $table = 'valuables_tracking';

    protected $casts = [
        'last_discovered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function sourceDominion()
    {
        return $this->belongsTo(Dominion::class, 'source_dominion_id');
    }

    public function targetDominion()
    {
        return $this->belongsTo(Dominion::class, 'target_dominion_id');
    }
}
