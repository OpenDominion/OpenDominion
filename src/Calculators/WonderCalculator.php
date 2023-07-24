<?php

namespace OpenDominion\Calculators;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
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
    protected const PRESTIGE_CONTRIBUTION_MULTIPLIER = 0;

    /**
     * @var float Minimum damage threshold for prestige gain
     */
    protected const PRESTIGE_CONTRIBUTION_MIN = 0.20;

    /**
     * @var float Maximum damage threshold for prestige gain
     */
    protected const PRESTIGE_CONTRIBUTION_MAX = 0.20;

    /**
     * @var float Minimum power after a wonder is rebuilt
     */
    protected const MIN_SPAWN_POWER = 15000;

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
        $timesDestroyed = GameEvent::where('type', 'wonder_destroyed')->where('source_id', $wonder->id)->count();
        return (static::MIN_SPAWN_POWER + (5000 * $timesDestroyed));

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
        $damageByRealm = min($this->getDamageDealtByRealm($wonder, $realm), $wonder->power);
        $damageContribution =  $damageByRealm / $wonder->power;
        $newPower = floor($maxPower * $damageContribution);
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

    /**
    * Calculates mastery gain for a dominion
    *
    * @param RoundWonder $wonder
    * @param Dominion $dominion
    * @return float
    */
    public function getMasteryGainForDominion(RoundWonder $wonder, Dominion $dominion): float
    {
        if ($wonder->realm == null || !$dominion->realm->wonders->isEmpty()) {
            // Wonder is neutral or realm already has a wonder
            return 0;
        }

        $damageByRealm = min($this->getDamageDealtByRealm($wonder, $dominion->realm), $wonder->power);
        $attackDamageByDominion = $this->getDamageDealtByDominion($wonder, $dominion, 'cyclone');

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
