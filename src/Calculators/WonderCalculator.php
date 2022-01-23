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
    protected const PRESTIGE_CONTRIBUTION_MULTIPLIER = 75;

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
     * @var float Maximum power after a neutral wonder is respawned
     */
    protected const MAX_SPAWN_POWER = 500000;

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
        $day = $wonder->round->daysInRound() + 2;

        if ($wonder->realm_id !== null) {
            $maxPower = min(42500 * $day, 2 * $wonder->power);
            $damageContribution = $this->getDamageDealtByRealm($wonder, $realm) / $wonder->power;
            $newPower = floor($maxPower * $damageContribution);
            return max(static::MIN_SPAWN_POWER, round($newPower, -4));
        }

        return min(static::MAX_SPAWN_POWER, 25000 * $day);
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
     * Returns the wonder's approximate power for out-of-realm display.
     *
     * @param RoundWonder $wonder
     * @return float
     */
    public function getApproximatePower(RoundWonder $wonder): float
    {
        $power = $this->getCurrentPower($wonder);
        $approximation = max(round($power, -4), 5000);

        if ($power == $wonder->power || $approximation > $wonder->power) {
            return $power;
        }

        return $approximation;
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
    * @param string $source
    * @return float
    */
    public function getDamageDealtByDominion(RoundWonder $wonder, Dominion $dominion, string $source = null): float
    {
        $wonderDamage = $wonder->damage()->where('dominion_id', $dominion->id);
        if ($source !== null) {
            return $wonderDamage->where('source', $source)->sum('damage');
        }

        return $wonderDamage->sum('damage');
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
        if ($wonder->realm == null || !$dominion->realm->wonders->isEmpty()) {
            // Wonder is neutral or realm already has a wonder
            return 0;
        }

        $damageByRealm = min($this->getDamageDealtByRealm($wonder, $dominion->realm), $wonder->power);
        $attackDamageByDominion = $this->getDamageDealtByDominion($wonder, $dominion, 'attack');

        $damageContribution = $attackDamageByDominion / $damageByRealm;
        if ($damageContribution < static::PRESTIGE_CONTRIBUTION_MIN) {
            return 0;
        }

        return round(static::PRESTIGE_BASE_GAIN + (
            min($damageContribution, static::PRESTIGE_CONTRIBUTION_MAX) *
            (static::PRESTIGE_CONTRIBUTION_MULTIPLIER / static::PRESTIGE_CONTRIBUTION_MAX)
        ));
    }
}
