<?php

namespace OpenDominion\Interfaces;

use OpenDominion\Models\Dominion;

interface DominionInitializableInterface
{
    /**
     * Initializes the class with a Dominion instance.
     *
     * @param Dominion $dominion
     * @return $this
     */
    public function init(Dominion $dominion);
}
