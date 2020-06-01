<?php

namespace OpenDominion\Models\Forum;

use OpenDominion\Models\AbstractModel;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;

/**
 * OpenDominion\Models\Form\Announcement
 *
 * @property int $id
 * @property int $round_id
 * @property string $title
 * @property string $body
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Round $round
 */
class Announcement extends AbstractModel
{
    protected $table = 'forum_announcements';

    public function round()
    {
        return $this->belongsTo(Round::class);
    }
}
