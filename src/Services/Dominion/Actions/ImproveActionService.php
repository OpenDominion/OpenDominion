<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
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

        $worth = $this->getImprovementWorth();

        foreach ($data as $improvementType => $amount) {
            if ($amount === 0) {
                continue;
            }

            if ($amount < 0) {
                throw new GameException('Investment aborted due to bad input.');
            }

            // Racial bonus multiplier
            $multiplier = (1 + $dominion->race->getPerkMultiplier('invest_bonus'));

            // Racial bonus ore multiplier
            if ($resource == 'ore') {
                $multiplier += $dominion->race->getPerkMultiplier('invest_bonus_ore');
            }

            $points = (($amount * $worth[$resource]) * $multiplier);
            $totalImprovements["improvement_{$improvementType}"] = $points;
        }

        DB::transaction(function() use ($dominion, $resource, $totalImprovements, $totalResourcesToInvest) {
            // Refresh in transaction to prevent race condition
            $dominion->refresh();
            foreach ($totalImprovements as $attr => $points) {
                $dominion->{$attr} += $points;
            }
            $dominion->{'resource_' . $resource} -= $totalResourcesToInvest;
            $dominion->save(['event' => HistoryService::EVENT_ACTION_IMPROVE]);
        });

        return [
            'message' => $this->getReturnMessageString($resource, $data, $totalResourcesToInvest),
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
        $worth = $this->getImprovementWorth();

        $investmentStringParts = [];

        foreach ($data as $improvementType => $amount) {
            if ($amount === 0) {
                continue;
            }

            $points = ($amount * $worth[$resource]);
            $investmentStringParts[] = (number_format($points) . ' ' . $improvementType);
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
