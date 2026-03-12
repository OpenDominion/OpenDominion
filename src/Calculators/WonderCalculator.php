<?php

namespace OpenDominion\Calculators;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Models\RoundWonder;
use OpenDominion\Models\Wonder;

class WonderCalculator
{
    /**
     * @var float Base gain for dominions over the minimum threshold
     */
    protected const PRESTIGE_BASE_GAIN = 25;

    /**
     * @var float Maximum potential gain from scaling damage contribution
     */
    protected const PRESTIGE_CONTRIBUTION_MULTIPLIER = 50;

    /**
     * @var float Minimum damage threshold for prestige gain
     */
    protected const PRESTIGE_CONTRIBUTION_MIN = 0.019;

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
     * Returns the wonder's power when being rebuilt.
     *
     * @param RoundWonder $wonder
     * @param Realm $realm
     * @return float
     */
    public function getNewPower(RoundWonder $wonder, Realm $realm): float
    {
        $day = $wonder->round->daysInRound();

        if ($wonder->realm == null || $wonder->realm_id == null) {
            // Built from or returning to neutral
            $dailyPower = 20000;
            if ($wonder->wonder->power == Wonder::TIER_ONE_POWER) {
                $dailyPower = 25000;
            }
            $newPower = $dailyPower * max(10, $day);
            if ($wonder->realm_id == null) {
                $newPower = min(static::MAX_SPAWN_POWER, $newPower);
            }
            return $newPower;
        }

        $maxPower = min(37500 * $day, 2 * $wonder->power);
        $damageByRealm = $this->getDamageDealtByRealm($wonder, $realm);
        $damageContribution =  $damageByRealm / $wonder->power;
        $newPower = rfloor($maxPower * $damageContribution);
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
        $damage = $wonder->damage()
            ->where('realm_id', $realm->id)
            ->sum('damage');

        return min($damage, $wonder->power);
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
     * Returns the damage contribution for a dominion
     *
     * @param RoundWonder $wonder
     * @param Dominion $dominion
     * @param string $source
     *
     * @return float
     */
    public function getDamageContribution(RoundWonder $wonder, Dominion $dominion, string|null $source = null): float
    {
        $damageByRealm = $this->getDamageDealtByRealm($wonder, $dominion->realm);
        $damageByDominion = $this->getDamageDealtByDominion($wonder, $dominion, $source);

        return $damageByDominion / $damageByRealm;
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
        if (!$dominion->realm->wonders->isEmpty()) {
            // Realm already has a wonder
            return 0;
        }

        $damageContribution = $this->getDamageContribution($wonder, $dominion, 'attack');
        if ($damageContribution < static::PRESTIGE_CONTRIBUTION_MIN) {
            return 0;
        }

        if ($wonder->realm == null) {
            // Wonder is neutral
            if ($damageContribution >= static::PRESTIGE_CONTRIBUTION_MIN) {
                return static::PRESTIGE_BASE_GAIN;
            }
        }

        return round(static::PRESTIGE_BASE_GAIN + (
            min($damageContribution, static::PRESTIGE_CONTRIBUTION_MAX) *
            (static::PRESTIGE_CONTRIBUTION_MULTIPLIER / static::PRESTIGE_CONTRIBUTION_MAX)
        ));
    }

    /**
    * Calculates mastery gain for a dominion
    *
    * @param RoundWonder $wonder
    * @param Dominion $dominion
    * @return float
    */
    public function getMasteryGainForDominion(RoundWonder $wonder, Dominion $dominion): float
    {
        if (!$dominion->realm->wonders->isEmpty()) {
            // Realm already has a wonder
            return 0;
        }

        $damageContribution = $this->getDamageContribution($wonder, $dominion, 'cyclone');
        if ($damageContribution < static::PRESTIGE_CONTRIBUTION_MIN) {
            return 0;
        }

        if ($wonder->realm == null) {
            // Wonder is neutral
            if ($damageContribution >= static::PRESTIGE_CONTRIBUTION_MIN) {
                return static::PRESTIGE_BASE_GAIN;
            }
        }

        return round(static::PRESTIGE_BASE_GAIN + (
            min($damageContribution, static::PRESTIGE_CONTRIBUTION_MAX) *
            (static::PRESTIGE_CONTRIBUTION_MULTIPLIER / static::PRESTIGE_CONTRIBUTION_MAX)
        ));
    }
}
