<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * OpenDominion\Models\RaidObjective
 *
 * @property int $id
 * @property int $raid_id
 * @property string $name
 * @property string $description
 * @property int $order
 * @property int $score_required
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Raid $raid
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\RaidObjectiveTactic[] $tactics
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\RaidObjective newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\RaidObjective newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\RaidObjective query()
 * @mixin \Eloquent
 */
class RaidObjective extends AbstractModel
{
    protected $fillable = [
        'raid_id',
        'name',
        'description',
        'order',
        'score_required',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function raid()
    {
        return $this->belongsTo(Raid::class);
    }

    public function tactics()
    {
        return $this->hasMany(RaidObjectiveTactic::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('start_date', '<=', now())
            ->where('end_date', '>', now());
    }

    public function getStatusAttribute(): string
    {
        if (!$this->hasStarted()) {
            return 'Upcoming';
        } elseif ($this->hasEnded()) {
            return 'Ended';
        } else {
            return 'In Progress';
        }
    }

    public function isActive(): bool
    {
        return $this->start_date <= now() && $this->end_date > now();
    }

    public function hasStarted(): bool
    {
        return $this->start_date <= now();
    }

    public function hasEnded(): bool
    {
        return $this->end_date <= now();
    }

    public function timeUntilEnd(): string
    {
        return now()->longAbsoluteDiffForHumans($this->end_date, 2);
    }

    public function timeUntilStart(): string
    {
        return now()->longAbsoluteDiffForHumans($this->start_date, 2);
    }
}
