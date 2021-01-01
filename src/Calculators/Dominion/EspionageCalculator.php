<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\EspionageHelper;
use OpenDominion\Models\Dominion;

class EspionageCalculator
{
    /** @var EspionageHelper */
    protected $espionageHelper;

    /**
     * EspionageCalculator constructor.
     *
     * @param EspionageHelper $espionageHelper
     */
    public function __construct(EspionageHelper $espionageHelper)
    {
        $this->espionageHelper = $espionageHelper;
    }

    public function canPerform(Dominion $dominion, string $operation): bool
    {
        $spyStrengthCost = 5;

        if ($this->espionageHelper->isInfoGatheringOperation($operation)) {
            $spyStrengthCost = 2;
        }

        return ($dominion->spy_strength >= 30);
    }
}
