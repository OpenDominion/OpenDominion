<?php

namespace OpenDominion\Services\Dominion;

use Carbon\Carbon;
use OpenDominion\Models\Dominion;

class ProtectionService
{
    public const PROTECTION_DURATION_IN_HOURS = 72; // todo: move to config?

    /**
     * Returns the Dominion's 'under protection' start date.
     *
     * @param Dominion $dominion
     * @return Carbon
     */
    public function getProtectionStartDate(Dominion $dominion): Carbon
    {
        $roundStartDate = Carbon::parse($dominion->round->start_date);
        $dominionCreatedDate = Carbon::parse($dominion->created_at);

        return (($dominionCreatedDate > $roundStartDate) ? $dominionCreatedDate : $roundStartDate);
    }

    /**
     * Returns the Dominion's 'under protection' end date.
     *
     * @param Dominion $dominion
     * @return Carbon
     */
    public function getProtectionEndDate(Dominion $dominion): Carbon
    {
        $modifiedStartDate = Carbon::parse($this->getProtectionStartDate($dominion)->format('Y-m-d H:00:00'));

        return $modifiedStartDate->addHours(self::PROTECTION_DURATION_IN_HOURS);
    }

    /**
     * Returns whether this Dominion instance is under protection.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function isUnderProtection(Dominion $dominion): bool
    {
        return ($this->getProtectionEndDate($dominion) >= now());
    }

    /**
     * Returns the hours the Dominion is still under protection for.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getUnderProtectionHoursLeft(Dominion $dominion): float
    {
        if (!$this->isUnderProtection($dominion)) {
            return 0;
        }

        $minutes = (int)now()->format('i');
        $seconds = (int)now()->format('s');

        $fraction = (1 - ((($minutes * 60) + $seconds) / 3600));

        return ($this->getProtectionEndDate($dominion)->diffInHours(now()) + $fraction);
    }
}
