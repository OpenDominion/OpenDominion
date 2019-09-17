<?php

namespace OpenDominion\Helpers;

class OpsHelper
{
    public function operationSuccessChance(float $selfRatio, float $targetRatio, float $multiplier): float
    {
        $ratio = $selfRatio / $targetRatio;
        $successRate = 0.8 ** (2 / (($ratio * $multiplier) ** 1.2));
        return clamp($successRate, 0, 1);
    }
}
