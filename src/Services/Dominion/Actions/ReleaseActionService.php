<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;

class ReleaseActionService
{
    use DominionGuardsTrait;

    /** @var UnitHelper */
    protected $unitHelper;

    /**
     * ReleaseActionService constructor.
     *
     * @param UnitHelper $unitHelper
     */
    public function __construct(UnitHelper $unitHelper)
    {
        $this->unitHelper = $unitHelper;
    }

    /**
     * Does a release troops action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $data
     * @return array
     * @throws RuntimeException
     */
    public function release(Dominion $dominion, array $data): array
    {
        $this->guardLockedDominion($dominion);

        $data = array_map('intval', $data);

        $troopsReleased = [];

        $totalTroopsToRelease = array_sum($data);

        if ($totalTroopsToRelease === 0) {
            throw new RuntimeException('Military release aborted due to bad input.');
        }

        foreach ($data as $unitType => $amount) {
            if ($amount === 0) { // todo: collect()->except(amount == 0)
                continue;
            }

            if ($amount > $dominion->{'military_' . $unitType}) {
                throw new RuntimeException('Military release was not completed due to bad input.');
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

        $dominion->save();

        // todo: refactor
        $troopsReleasedStringParts = ['You successfully released'];
        $troopsReleasedStringPartsParts = []; // todo: refactooooooor

        foreach ($troopsReleased as $unitType => $amount) {
            if ($amount === 0) {
                continue;
            }

            if ($unitType === 'draftees') {
                $troopsReleasedStringParts[] = sprintf('%s %s into the peasantry', number_format($amount), str_plural('draftee', $amount));
            } else {
                $troopsReleasedStringPartsParts[] = (number_format($amount) . ' ' . str_plural(str_singular(strtolower($this->unitHelper->getUnitName($unitType, $dominion->race))), $amount));
            }
        }

        if (!empty($troopsReleasedStringPartsParts)) {
            $tmp = implode(', ', $troopsReleasedStringPartsParts);
            $tmp = strrev(implode(strrev(' and '), explode(strrev(', '), strrev($tmp), 2)));

            if (isset($troopsReleased['draftees'])) {
                $troopsReleasedStringParts[] = 'and';
            }

            $troopsReleasedStringParts[] = $tmp;
            $troopsReleasedStringParts[] = 'into draftees';
        }

        $troopsReleasedString = (implode(' ', $troopsReleasedStringParts) . '.');
        // todo: /refactor

        return [
            'message' => $troopsReleasedString,
            'totalTroopsReleased' => $totalTroopsToRelease,
        ];
    }
}
