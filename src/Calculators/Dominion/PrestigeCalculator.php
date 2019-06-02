<?php


namespace OpenDominion\Calculators\Dominion;


use OpenDominion\Models\Dominion;

class PrestigeCalculator
{
    public function getPrestigeMultiplier(Dominion $dominion): float
    {
        return ($dominion->prestige / 100) / 100;
    }
}