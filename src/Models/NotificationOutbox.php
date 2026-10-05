<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationOutbox extends AbstractModel
{
    protected $table = 'notification_outbox';

    protected $casts = [
        'payload' => 'array',
        'email_allowed' => 'boolean',
        'event_at' => 'datetime',
        'available_at' => 'datetime',
        'web_delivered_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function dominion(): BelongsTo
    {
        return $this->belongsTo(Dominion::class);
    }
}
