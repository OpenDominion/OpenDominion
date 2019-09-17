<?php

namespace OpenDominion\Events;

use Illuminate\Queue\SerializesModels;
use OpenDominion\Models\InfoOp;

class InfoOpCreatingEvent
{
    use SerializesModels;

    public $infoOp;

    /**
     * Create a new event instance.
     *
     * @param InfoOp $infoOp
     */
    public function __construct(InfoOp $infoOp)
    {
        $this->infoOp = $infoOp;
    }
}
