<?php

namespace OpenDominion\Models;

use Webpatser\Uuid\Uuid;

class GameEvent extends AbstractModel
{
    public $incrementing = false;

    protected $casts = [
        'data' => 'array',
    ];

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function source()
    {
        return $this->morphTo();
    }

    public function target()
    {
        return $this->morphTo();
    }

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->id = Uuid::generate();
        });
    }
}
