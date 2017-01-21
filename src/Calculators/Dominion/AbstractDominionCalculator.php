<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Interfaces\DependencyInitializableInterface;
use OpenDominion\Interfaces\DominionInitializableInterface;
use OpenDominion\Models\Dominion;
use OpenDominion\Traits\DominionAwareTrait;

abstract class AbstractDominionCalculator implements DependencyInitializableInterface, DominionInitializableInterface
{
    use DominionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function init(Dominion $dominion)
    {
        $this->setDominion($dominion);
        return $this;
    }
}
