<?php

namespace OpenDominion\Services;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Valor;

class ValorService
{
    public const BONUS_VALOR_HOTR_BASE = 5;
    public const BONUS_VALOR_HOTR_DAY_MULTIPLIER = 0.4;
    public const BONUS_VALOR_WAR_HIT = 10;
    public const BONUS_VALOR_WONDER = 25;
    public const BONUS_VALOR_WONDER_NEUTRAL = 10;

    /**
     * Creates a record of valor gain by an individual dominion.
     *
     * @param Dominion $dominion
     * @param string $source
     * @param float $amount
     *
     * @return bool
     */
    public function awardValor(Dominion $dominion, string $source, float $amount = 0): bool
    {
        if ($source == 'hotr') {
            $amount = self::BONUS_VALOR_HOTR_BASE;
            $amount += $dominions->round->daysInRound() * self::BONUS_VALOR_HOTR_DAY_MULTIPLIER;
        } elseif ($source == 'war_hit') {
            $amount = self::BONUS_VALOR_WAR_HIT;
        } elseif ($source == 'wonder') {
            $amount *= self::BONUS_VALOR_WONDER;
        } elseif ($source == 'wonder_neutral') {
            $amount *= self::BONUS_VALOR_WONDER_NEUTRAL;
        }

        return Valor::create([
            'round_id' => $dominion->round_id,
            'realm_id' => $dominion->realm_id,
            'dominion_id' => $dominion->id,
            'source' => $source,
            'amount' => $amount
        ]);
    }
}
