<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationOutbox extends AbstractModel
{
    protected $table = 'notification_outbox';

    protected $casts = [
        'payload' => 'array',
        'event_at' => 'datetime',
        'available_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function dominion(): BelongsTo
    {
        return $this->belongsTo(Dominion::class);
    }
}
