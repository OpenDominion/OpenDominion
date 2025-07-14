<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * OpenDominion\Models\Raid
 *
 * @property int $id
 * @property int $round_id
 * @property string $name
 * @property string $description
 * @property string $reward_resource
 * @property int $reward_amount
 * @property string $completion_reward_resource
 * @property int $completion_reward_amount
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Round $round
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Raid newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Raid newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Raid query()
 * @mixin \Eloquent
 */
class Raid extends AbstractModel
{
    protected $fillable = [
        'round_id',
        'name',
        'description',
        'reward_resource',
        'reward_amount',
        'completion_reward_resource',
        'completion_reward_amount',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function objectives()
    {
        return $this->hasMany(RaidObjective::class);
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
            return 'Completed';
        } else {
            return 'In Progress';
        }
    }

    public function getOrderAttribute(): int
    {
        $sortMap = [
            'In Progress' => 1,
            'Upcoming' => 2,
            'Completed' => 3
        ];
        return $sortMap[$this->status];
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
