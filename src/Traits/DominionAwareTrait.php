<?php

namespace OpenDominion\Traits;

use OpenDominion\Models\Dominion;

trait DominionAwareTrait
{
    /** @var Dominion */
    protected $dominion;

    /**
     * @param Dominion $dominion
     * @return $this
     */
    public function setDominion(Dominion $dominion)
    {
        $this->dominion = $dominion;
        return $this;
    }
}
