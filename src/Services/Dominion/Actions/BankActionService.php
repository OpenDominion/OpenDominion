<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Contracts\Calculators\Dominion\Actions\BankingCalculator;
use OpenDominion\Contracts\Services\Dominion\Actions\BankActionService as BankActionServiceContract;
use OpenDominion\Models\Dominion;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;

class BankActionService implements BankActionServiceContract
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
     * {@inheritdoc}
     *
     * @throws \RuntimeException
     */
    public function exchange(Dominion $dominion, $source, $target, $amount)
    {
        $this->guardLockedDominion($dominion);

        // Get the resource information.
        $resources = $this->bankingCalculator->getResources($dominion);
        if (empty($resources[$source])) {
            throw new RuntimeException('Failed to find resource ' . $source);
        }
        if (empty($resources[$target])) {
            throw new RuntimeException('Failed to find resource ' . $target);
        }
        $sourceResource = $resources[$source];
        $targetResource = $resources[$target];

        if ($amount > $dominion->{$source}) {
            throw new RuntimeException('You do not have ' . number_format($amount) . ' ' . $sourceResource['label'] . ' to exchange.');
        }

        $targetAmount = floor($amount * $sourceResource['sell'] * $targetResource['buy']);

        $dominion->{$source} -= $amount;
        $dominion->{$target} += $targetAmount;

        $dominion->save();

    }
}
