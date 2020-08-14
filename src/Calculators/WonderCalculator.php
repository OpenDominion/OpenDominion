<?php

namespace OpenDominion\Calculators;

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
     * Returns the wonder's starting power.
     *
     * @param RoundWonder $wonder
     * @param Realm $realm
     * @return float
     */
    public function getSpawnPower(RoundWonder $wonder, ?Realm $realm): float
    {
        if ($realm !== null) {
            $damageContribution = $this->getDamageDealtByRealm($wonder, $realm) / $wonder->power;
            $multiplier = ($wonder->round->daysInRound() / 5) * $damageContribution;
        } else {
            $multiplier = ($wonder->round->daysInRound() / 10);
        }
        return min(static::MIN_SPAWN_POWER, $wonder->wonder->power * $multiplier);
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

        return static::PRESTIGE_BASE_GAIN + (
            min($damageContribution, static::PRESTIGE_CONTRIBUTION_MAX) *
            (static::PRESTIGE_CONTRIBUTION_MULTIPLIER / static::PRESTIGE_CONTRIBUTION_MAX)
        );
    }
}
