<?php

namespace OpenDominion\Calculators;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Models\RoundWonder;

class WonderCalculator
{
    /**
     * @var float Base gain for dominions over the minimum threshold
     */
    protected const PRESTIGE_BASE_GAIN = 25;

    /**
     * @var float Maximum potential gain from scaling damage contribution
     */
    protected const PRESTIGE_CONTRIBUTION_MULTIPLIER = 100;

    /**
     * @var float Minimum damage threshold for prestige gain
     */
    protected const PRESTIGE_CONTRIBUTION_MIN = 0.02;

    /**
     * @var float Maximum damage threshold for prestige gain
     */
    protected const PRESTIGE_CONTRIBUTION_MAX = 0.20;

    /**
     * @var float Minimum power after a wonder is rebuilt
     */
    protected const MIN_SPAWN_POWER = 150000;

    /**
     * @var float Constraints for RP gain formula
     */
    protected const TECH_MAX_REWARD = 2500;
    protected const TECH_MIN_SIZE = 590;

    /**
     * Returns the wonder's power when being rebuilt.
     *
     * @param RoundWonder $wonder
     * @param Realm $realm
     * @return float
     */
    public function getNewPower(RoundWonder $wonder, Realm $realm): float
    {
        $day = $wonder->round->daysInRound() - 1;
        if ($wonder->realm !== null) {
            $maxPower = min(42500 * $day, 2 * $wonder->power);
            $damageContribution = $this->getDamageDealtByRealm($wonder, $realm) / $wonder->power;
            $newPower = floor($maxPower * $damageContribution);
        } else {
            $newPower = 25000 * $day;
        }
        return max(static::MIN_SPAWN_POWER, round($newPower, -4));
    }

    /**
     * Returns the wonder's current power.
     *
     * @param RoundWonder $wonder
     * @return float
     */
    public function getCurrentPower(RoundWonder $wonder): float
    {
        return max(0, $wonder->power - $this->getDamageDealt($wonder));
    }

    /**
    * Returns total damage dealt to a wonder
    *
    * @param RoundWonder $wonder
    * @return float
    */
    public function getDamageDealt(RoundWonder $wonder): float
    {
        return $wonder->damage()
            ->sum('damage');
    }

    /**
    * Returns damage dealt by a realm
    *
    * @param RoundWonder $wonder
    * @param Realm $realm
    * @return float
    */
    public function getDamageDealtByRealm(RoundWonder $wonder, Realm $realm): float
    {
        return $wonder->damage()
            ->where('realm_id', $realm->id)
            ->sum('damage');
    }

    /**
    * Returns damage dealt by a single dominion
    *
    * @param RoundWonder $wonder
    * @param Dominion $dominion
    * @return float
    */
    public function getDamageDealtByDominion(RoundWonder $wonder, Dominion $dominion): float
    {
        return $wonder->damage()
            ->where('dominion_id', $dominion->id)
            ->sum('damage');
    }

    /**
    * Calculates prestige gain for a dominion
    *
    * @param RoundWonder $wonder
    * @param Dominion $dominion
    * @return float
    */
    public function getPrestigeGainForDominion(RoundWonder $wonder, Dominion $dominion): float
    {
        $damageByRealm = $this->getDamageDealtByRealm($wonder, $dominion->realm);
        $damageByDominion = $this->getDamageDealtByDominion($wonder, $dominion);
        $damageContribution = $damageByDominion / $damageByRealm;

        if ($damageContribution < static::PRESTIGE_CONTRIBUTION_MIN) {
            return 0;
        }

        return round(static::PRESTIGE_BASE_GAIN + (
            min($damageContribution, static::PRESTIGE_CONTRIBUTION_MAX) *
            (static::PRESTIGE_CONTRIBUTION_MULTIPLIER / static::PRESTIGE_CONTRIBUTION_MAX)
        ));
    }

    /**
    * Calculates research point gain for a dominion
    *
    * @param RoundWonder $wonder
    * @param Dominion $dominion
    * @param float $offenseSent
    * @return float
    */
    public function getTechGainForDominion(RoundWonder $wonder, Dominion $dominion, float $offenseSent): float
    {
        $offenseRequired = $wonder->power * min(15, $dominion->round->daysInRound() - 3) / 100;
        if ($offenseSent < $offenseRequired) {
            return 0;
        }

        $landCalculator = app(LandCalculator::class);
        $totalLand = $landCalculator->getTotalLand($dominion);

        $techGain = min(static::TECH_MAX_REWARD * static::TECH_MIN_SIZE / $totalLand, static::TECH_MAX_REWARD);

        return $techGain;
    }
}
