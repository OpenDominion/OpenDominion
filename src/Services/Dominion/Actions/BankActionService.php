<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Calculators\Dominion\Actions\BankingCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;

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
     * @throws RuntimeException
     */
    public function exchange(Dominion $dominion, string $source, string $target, int $amount): array
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

        $dominion->decrement($source, $amount);
        $dominion->increment($target, $targetAmount);
        $dominion->save(['event' => HistoryService::EVENT_ACTION_BANK]);

        return [
            'message' => 'Your resources have been exchanged.',
        ];
    }
}
