<?php

namespace OpenDominion\Calculators\Dominion\Actions;

use OpenDominion\Contracts\Calculators\Dominion\Actions\BankingCalculator as BankingCalculatorContract;
use OpenDominion\Models\Dominion;

class BankingCalculator implements BankingCalculatorContract
{
    /**
     * {@inheritdoc}
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

        return $resources;
    }
}
