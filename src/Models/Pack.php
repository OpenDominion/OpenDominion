<?php

namespace OpenDominion\Models;

use Carbon\Carbon;

class Pack extends AbstractModel
{
    protected $dates = ['closed_at', 'created_at', 'updated_at'];

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dominions()
    {
        return $this->hasMany(Dominion::class);
    }

    public function realm()
    {
        return $this->belongsTo(Realm::class);
    }

    public function isFull(): bool
    {
        return ($this->dominions->count() === $this->size);
    }

    public function isClosed(): bool
    {
        return (($this->closed_at !== null) || $this->getClosingDate() < now());
    }

    public function getClosingDate(): Carbon
    {
        return max($this->created_at, $this->round->start_date)->addDays(3);
    }
}
