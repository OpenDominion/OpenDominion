<?php

namespace OpenDominion\Services\Actions;

use OpenDominion\Models\Dominion;
use OpenDominion\Traits\DominionAwareTrait;

abstract class AbstractActionService
{
    use DominionAwareTrait;

    public function __construct(Dominion $dominion)
    {
        $this->setDominion($dominion);
    }
}
