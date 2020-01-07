<?php

namespace OpenDominion\Helpers;

class OpsHelper
{
    public function infoOperationSuccessChance(float $selfRatio, float $targetRatio): float
    {
        $ratio = $selfRatio / $targetRatio;
        $successRate = 0.8 ** (2 / (($ratio * 1.4) ** 1.2));
        return clamp($successRate, 0.03, 0.97);
    }

    public function theftOperationSuccessChance(float $selfRatio, float $targetRatio): float
    {
        $ratio = $selfRatio / $targetRatio;
        $successRate = 0.6 ** (2 / (($ratio * 1.2) ** 1.2));
        return clamp($successRate, 0.03, 0.97);
    }

    public function blackOperationSuccessChance(float $selfRatio, float $targetRatio): float
    {
        $ratioRelative = $selfRatio / $targetRatio;
        $ratioDifference = $selfRatio - $targetRatio;
        $successRate = (
            max(0, (0.08 * $ratioDifference)) +
            ((($ratioRelative ** 0.6) * 0.25) ** 0.6) +
            min(0, (0.05 * $ratioDifference))
        );
        return clamp($successRate, 0.03, 0.97);
    }
}
