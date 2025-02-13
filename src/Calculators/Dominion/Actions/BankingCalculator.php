<?php

namespace OpenDominion\Calculators\Dominion\Actions;

use OpenDominion\Models\Dominion;

class BankingCalculator
{
    /**
     * Returns the exchange rate bonus for a Dominion
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getExchangeBonus(Dominion $dominion): float
    {
        $multiplier = 1;

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('exchange_bonus');

        // Wonder
        $multiplier += $dominion->getWonderPerkMultiplier('exchange_bonus');

        return $multiplier;
    }

    /**
     * Returns resources and prices for exchanging.
     *
     * @param Dominion $dominion
     * @return array
     */
    public function getResources(Dominion $dominion): array
    {
        $resources = [
            'resource_platinum' => [
                'label' => 'Platinum',
                'buy' => 1.0,
                'sell' => 0.5,
                'max' => $dominion->resource_platinum,
            ],
            'resource_lumber' => [
                'label' => 'Lumber',
                'buy' => 1.0,
                'sell' => 0.5,
                'max' => $dominion->resource_lumber,
            ],
            'resource_ore' => [
                'label' => 'Ore',
                'buy' => 1.0,
                'sell' => 0.5,
                'max' => $dominion->resource_ore,
            ],
            'resource_gems' => [
                'label' => 'Gems',
                'buy' => 0.0,
                'sell' => 2.0,
                'max' => $dominion->resource_gems,
            ],
            'resource_food' => [
                'label' => 'Food',
                'buy' => 0.5,
                'sell' => 0.0,
                'max' => $dominion->resource_food,
            ],
        ];

        // Heroes
        if ($dominion->hero !== null) {
            $manaExchangeRate = $dominion->hero->getPerkValue('exchange_mana');
            if ($manaExchangeRate) {
                $resources['resource_mana'] = [
                    'label' => 'Mana',
                    'buy' => 0.0,
                    'sell' => $manaExchangeRate,
                    'max' => $dominion->resource_mana,
                ];
            }
        }

        return $resources;
    }
}
