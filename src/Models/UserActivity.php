<?php

namespace OpenDominion\Models;

class UserActivity extends AbstractModel // todo: AbstractReadOnlyModel
{
    protected $guarded = ['id', 'created_at'];

    protected $dates = ['created_at'];

    protected $casts = [
        'context' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function setUpdatedAt($value)
    {
        return $this;
    }
}
