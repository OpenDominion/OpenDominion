<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;

class EspionageCalculator
{
    public function canPerform(Dominion $dominion, string $operationKey): bool
    {
        return ($dominion->spy_strength >= 30);
    }
}
