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
        $ratio = $selfRatio / $targetRatio;
        $successRate = ((($ratio ** 0.6) * 0.25) ** 0.6);
        return clamp($successRate, 0.03, 0.97);
    }
}
