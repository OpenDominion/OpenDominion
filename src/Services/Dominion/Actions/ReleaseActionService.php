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

        $troopsReleasedStringParts = ['You successfully released'];

        if (isset($troopsReleased['draftees'])) {
            $amount = $troopsReleased['draftees'];
            $troopsReleasedStringParts[] = sprintf('%s %s into the peasantry', number_format($amount), str_plural('draftee', $amount));
        }

        $troopsCreated = FALSE;
        foreach ($troopsReleased as $unitType => $amount) {
            if ($unitType === 'draftees') {
                continue;
            }
            else {
                $troopsCreated = TRUE;
                $conjunction = count($troopsReleasedStringParts) === 1 ? '' : 'and ';
                $troopsReleasedStringParts[] = $conjunction . (number_format($amount) . ' ' . str_plural(str_singular(strtolower($this->unitHelper->getUnitName($unitType, $dominion->race))), $amount));
            }
        }

        if ($troopsCreated) {
            $troopsReleasedStringParts[] = 'into draftees';
        }

        $troopsReleasedString = (implode(' ', $troopsReleasedStringParts) . '.');

        return [
            'message' => $troopsReleasedString,
            'totalTroopsReleased' => $totalTroopsToRelease,
        ];
    }
}
