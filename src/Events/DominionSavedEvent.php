<?php

namespace OpenDominion\Events;

use Illuminate\Queue\SerializesModels;
use OpenDominion\Models\Dominion;

class DominionSavedEvent
{
    use SerializesModels;

    public $dominion;

    /**
     * Create a new event instance.
     *
     * @param Dominion $dominion
     */
    public function __construct(Dominion $dominion)
    {
        $this->dominion = $dominion;
    }
}
