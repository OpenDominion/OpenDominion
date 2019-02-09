<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;

class EspionageCalculator
{
    // todo: clean this up
    public function canPerform(Dominion $dominion, string $operationKey): bool
    {
        return ($dominion->spy_strength >= 30);
    }
}
