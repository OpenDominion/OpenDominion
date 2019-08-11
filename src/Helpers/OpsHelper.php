<?php

namespace OpenDominion\Helpers;

class OpsHelper
{
    public function operationSuccessChance(float $selfRatio, float $targetRatio, float $base): float
    {
        $ratioDifference = $targetRatio - $selfRatio;
        $successRate = min($base, $base * $selfRatio * 5) * ((1 - $ratioDifference / 3) * (1/3) + ($selfRatio / $targetRatio) * (2/3));
        return clamp($successRate, 0, 1);
    }
}
