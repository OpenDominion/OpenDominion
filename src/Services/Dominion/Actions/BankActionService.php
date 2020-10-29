<?php

namespace OpenDominion\Services\Dominion\Actions;

use LogicException;
use OpenDominion\Calculators\Dominion\Actions\BankingCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Traits\DominionGuardsTrait;

class BankActionService
{
    use DominionGuardsTrait;

    /** @var BankingCalculator */
    protected $bankingCalculator;

    /**
     * BankActionService constructor.
     *
     * @param BankingCalculator $bankingCalculator
     */
    public function __construct(BankingCalculator $bankingCalculator)
    {
        $this->bankingCalculator = $bankingCalculator;
    }

    /**
     * Does a bank action for a Dominion.
     *
     * @param Dominion $dominion
     * @param string $source
     * @param string $target
     * @param int $amount
     * @return array
     * @throws LogicException
     * @throws GameException
     */
    public function exchange(Dominion $dominion, string $source, string $target, int $amount): array
    {
        $this->guardLockedDominion($dominion);

        if ($amount < 0) {
            throw new LogicException('Amount less than 0.');
        }

        // Get the resource information.
        $resources = $this->bankingCalculator->getResources($dominion);
        if (empty($resources[$source])) {
            throw new LogicException('Failed to find resource ' . $source);
        }
        if (empty($resources[$target])) {
            throw new LogicException('Failed to find resource ' . $target);
        }
        $sourceResource = $resources[$source];
        $targetResource = $resources[$target];

        if ($amount > $dominion->{$source}) {
            throw new GameException(sprintf(
                'You do not have %s %s to exchange.',
                number_format($amount),
                $sourceResource['label']
            ));
        }

        $multiplier = 1;

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('exchange_bonus');

        // Wonder
        $multiplier += $dominion->getWonderPerkMultiplier('exchange_bonus');

        $targetAmount = floor($amount * $sourceResource['sell'] * $targetResource['buy'] * $multiplier);

        $dominion->{$source} -= $amount;
        $dominion->{$target} += $targetAmount;

        $dominion->save(['event' => HistoryService::EVENT_ACTION_BANK]);

        return [
            'message' => sprintf(
                'You have exchanged %s %s for %s %s.',
                $amount,
                dominion_attr_display($source, $amount),
                $targetAmount,
                dominion_attr_display($target, $targetAmount)
            )
        ];
    }
}
