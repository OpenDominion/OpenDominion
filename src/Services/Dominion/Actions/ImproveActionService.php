<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Traits\DominionGuardsTrait;

class ImproveActionService
{
    use DominionGuardsTrait;

    public function improve(Dominion $dominion, string $resource, array $data): array
    {
        $this->guardLockedDominion($dominion);

        $data = array_map('\intval', $data);

        $totalResourcesToInvest = array_sum($data);

        if ($totalResourcesToInvest === 0) {
            throw new GameException('Investment aborted due to bad input.');
        }

        if (!\in_array($resource, ['platinum', 'lumber', 'ore', 'gems'], true)) {
            throw new GameException('Investment aborted due to bad resource type.');
        }

        if ($totalResourcesToInvest > $dominion->{'resource_' . $resource}) {
            throw new GameException("You do not have enough {$resource} to invest.");
        }

        $improvementCalculator = app(ImprovementCalculator::class);
        $repairableImprovements = $improvementCalculator->getRepairableImprovements($dominion);
        $repairMultiplier = (1 + $improvementCalculator->getRepairMultiplier($dominion));
        $worth = $this->getImprovementWorth();

        foreach ($data as $improvementType => $amount) {
            if ($amount === 0) {
                continue;
            }

            if ($amount < 0) {
                throw new GameException('Investment aborted due to bad input.');
            }

            $multiplier = $improvementCalculator->getInvestmentMultiplier($dominion, $resource, $improvementType);

            $points = floor($amount * $worth[$resource] * $multiplier);
            if ($repairableImprovements > 0) {
                $points = min($points * $repairMultiplier, $points + $repairableImprovements);
                $repairableImprovements -= $points;
            }

            $dominion->{"improvement_{$improvementType}"} += $points;
            $result[$improvementType] = $points;
        }

        $totalImprovements = $improvementCalculator->getImprovementTotal($dominion);
        if ($totalImprovements > $dominion->highest_improvement_total) {
            $dominion->highest_improvement_total = $totalImprovements;
        }
        $dominion->{'resource_' . $resource} -= $totalResourcesToInvest;
        $dominion->{'stat_total_' . $resource . '_spent_investment'} += $totalResourcesToInvest;
        $dominion->save(['event' => HistoryService::EVENT_ACTION_IMPROVE]);

        return [
            'message' => $this->getReturnMessageString($resource, $result, $totalResourcesToInvest),
            'data' => [
                'totalResourcesInvested' => $totalResourcesToInvest,
                'resourceInvested' => $resource,
            ],
        ];
    }

    /**
     * Returns the message for a improve action.
     *
     * @param string $resource
     * @param array $data
     * @param int $totalResourcesToInvest
     * @return string
     */
    protected function getReturnMessageString(string $resource, array $data, int $totalResourcesToInvest): string
    {
        $investmentStringParts = [];

        foreach ($data as $improvementType => $worth) {
            if ($worth === 0) {
                continue;
            }

            $investmentStringParts[] = (number_format($worth) . ' ' . $improvementType);
        }

        $investmentString = generate_sentence_from_array($investmentStringParts);

        return sprintf(
            'You invest %s %s into %s.',
            number_format($totalResourcesToInvest),
            ($resource === 'gems') ? str_plural('gem', $totalResourcesToInvest) : $resource,
            $investmentString
        );
    }

    /**
     * Returns the amount of points per resource type invested.
     *
     * @return array
     */
    public function getImprovementWorth(): array
    {
        return [
            'platinum' => 1,
            'lumber' => 2,
            'ore' => 2,
            'gems' => 12,
        ];
    }
}
