<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Traits\DominionGuardsTrait;

class ReleaseActionService
{
    use DominionGuardsTrait;

    /** @var UnitHelper */
    protected $unitHelper;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var ProtectionService */
    protected $protectionService;

    /**
     * ReleaseActionService constructor.
     *
     * @param UnitHelper $unitHelper
     */
    public function __construct(UnitHelper $unitHelper, MilitaryCalculator $militaryCalculator, ProtectionService $protectionService)
    {
        $this->unitHelper = $unitHelper;
        $this->militaryCalculator = $militaryCalculator;
        $this->protectionService = $protectionService;
    }

    /**
     * Does a release troops action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $data
     * @return array
     * @throws GameException
     */
    public function release(Dominion $dominion, array $data): array
    {
        $this->guardLockedDominion($dominion);

        $data = array_map('\intval', $data);

        $troopsReleased = [];

        $totalTroopsToRelease = array_sum($data);
        if ($totalTroopsToRelease <= 0) {
            throw new GameException('Military release aborted due to bad input.');
        }

        $units = [
            1 => array_get($data, 'unit1', 0),
            2 => array_get($data, 'unit2', 0),
            3 => array_get($data, 'unit3', 0),
            4 => array_get($data, 'unit4', 0)
        ];

        $rawDpReleased = $this->militaryCalculator->getDefensivePowerRaw($dominion, null, null, $units, true);

        if ($rawDpReleased > 0 && !$this->protectionService->isUnderProtection($dominion))
        {
            // Check for excessive release restriction
            $defenseBeforeRelease = $this->militaryCalculator->getDefensivePowerRaw($dominion);
            $defenseReducedRecently = $this->militaryCalculator->getDefenseReducedRecently($dominion);
            if ((($rawDpReleased + $defenseReducedRecently) / ($defenseBeforeRelease + $defenseReducedRecently)) > 0.15) {
                throw new GameException('You cannot release more than 15% of your raw defense during a 24 hour period.');
            }
        }

        foreach ($data as $unitType => $amount) {
            if ($amount === 0) { // todo: collect()->except(amount == 0)
                continue;
            }

            if ($amount < 0) {
                throw new GameException('Military release aborted due to bad input.');
            }

            if ($amount > $dominion->{'military_' . $unitType}) {
                throw new GameException('Military release was not completed due to bad input.');
            }
        }

        foreach ($data as $unitType => $amount) {
            if ($amount === 0) {
                continue;
            }

            $dominion->{'military_' . $unitType} -= $amount;
            if ($unitType === 'draftees') {
                $dominion->peasants += $amount;
            } else {
                $dominion->military_draftees += $amount;
            }

            $troopsReleased[$unitType] = $amount;
        }

        $dominion->save([
            'event' => HistoryService::EVENT_ACTION_RELEASE,
            'defense_reduced' => $rawDpReleased
        ]);

        return [
            'message' => $this->getReturnMessageString($dominion, $troopsReleased),
            'data' => [
                'totalTroopsReleased' => $totalTroopsToRelease,
            ],
        ];
    }

    /**
     * Returns the message for a release action.
     *
     * @param Dominion $dominion
     * @param array $troopsReleased
     * @return string
     */
    protected function getReturnMessageString(Dominion $dominion, array $troopsReleased): string
    {
        $stringParts = ['You successfully released'];

        // Draftees into peasants
        if (isset($troopsReleased['draftees'])) {
            $amount = $troopsReleased['draftees'];
            $stringParts[] = sprintf('%s %s into the peasantry', number_format($amount), str_plural('draftee', $amount));
        }

        // Troops into draftees
        $troopsParts = [];
        foreach ($troopsReleased as $unitType => $amount) {
            if ($unitType === 'draftees') {
                continue;
            }

            $unitName = str_singular(strtolower($this->unitHelper->getUnitName($unitType, $dominion->race)));
            $troopsParts[] = (number_format($amount) . ' ' . str_plural($unitName, $amount));
        }

        if (!empty($troopsParts)) {
            if (\count($stringParts) === 2) {
                $stringParts[] = 'and';
            }

            $stringParts[] = generate_sentence_from_array($troopsParts);
            $stringParts[] = 'into draftees';
        }

        return (implode(' ', $stringParts) . '.');
    }
}
