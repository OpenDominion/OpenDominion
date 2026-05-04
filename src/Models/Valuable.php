<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * OpenDominion\Models\Valuable
 *
 * @property int $id
 * @property int $round_id
 * @property int $source_dominion_id
 * @property int $target_dominion_id
 * @property string $name
 * @property string $rarity
 * @property string $type
 * @property string $status
 * @property int|null $required_spy_hours
 * @property int|null $spies_assigned
 * @property \Illuminate\Support\Carbon|null $investigation_started_at
 * @property \Illuminate\Support\Carbon|null $investigation_ends_at
 * @property \Illuminate\Support\Carbon|null $stolen_at
 * @property \Illuminate\Support\Carbon $discovered_at
 * @property int $transfer_price
 * @property bool $is_listed
 * @property int|null $sold_price
 * @property bool $transferred
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Round $round
 * @property-read \OpenDominion\Models\Dominion $sourceDominion
 * @property-read \OpenDominion\Models\Dominion $targetDominion
 */
class Valuable extends AbstractModel
{
    protected $table = 'valuables';

    protected $casts = [
        'is_listed' => 'boolean',
        'transferred' => 'boolean',
        'investigation_started_at' => 'datetime',
        'investigation_ends_at' => 'datetime',
        'stolen_at' => 'datetime',
        'discovered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const STATUS_DISCOVERED = 'discovered';
    public const STATUS_INVESTIGATING = 'investigating';
    public const STATUS_STOLEN = 'stolen';
    public const STATUS_SOLD = 'sold';
    public const STATUS_LISTED_FOR_TRANSFER = 'listed_for_transfer';
    public const STATUS_TRANSFERRED = 'transferred';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_FAILED = 'failed';

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

    // Eloquent Query Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            self::STATUS_SOLD,
            self::STATUS_EXPIRED,
            self::STATUS_FAILED,
        ]);
    }

    public function scopeInvestigating(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_INVESTIGATING);
    }

    public function scopeListed(Builder $query): Builder
    {
        return $query->where('is_listed', true);
    }

    // Helpers

    public function isActiveInvestigation(): bool
    {
        return $this->status === self::STATUS_INVESTIGATING;
    }

    public function hoursOld(): float
    {
        return $this->discovered_at->diffInSeconds(now()) / 3600;
    }
}
