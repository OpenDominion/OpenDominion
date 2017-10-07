<?php

namespace OpenDominion\Services\Dominion;

use Carbon\Carbon;
use OpenDominion\Models\Dominion;

class ProtectionService
{
    const PROTECTION_DURATION_IN_HOURS = 72; // todo: move to config?

    /**
     * Returns the Dominion's 'under protection' start date.
     *
     * @return Carbon
     */
    public function getProtectionStartDate(Dominion $dominion)
    {
        $roundStartDate = Carbon::parse($dominion->round->start_date);
        $dominionCreatedDate = Carbon::parse($dominion->created_at);

        return (($dominionCreatedDate > $roundStartDate) ? $dominionCreatedDate : $roundStartDate);
    }

    /**
     * Returns the Dominion's 'under protection' end date.
     *
     * @return Carbon
     */
    public function getProtectionEndDate(Dominion $dominion)
    {
        $modifiedStartDate = Carbon::parse($this->getProtectionStartDate($dominion)->format('Y-m-d H:00:00'));

        return $modifiedStartDate->addHours(self::PROTECTION_DURATION_IN_HOURS);
    }

    /**
     * Returns whether this Dominion instance is under protection.
     *
     * @return bool
     */
    public function isUnderProtection(Dominion $dominion)
    {
        return ($this->getProtectionEndDate($dominion) >= Carbon::now());
    }

    /**
     * Returns the hours the Dominion is still under protection for.
     *
     * @return float
     */
    public function getUnderProtectionHoursLeft(Dominion $dominion)
    {
        if (!$this->isUnderProtection($dominion)) {
            return 0;
        }

        $minutes = (int)Carbon::now()->format('i');
        $seconds = (int)Carbon::now()->format('s');

        $fraction = (1 - ((($minutes * 60) + $seconds) / 3600));

        return ($this->getProtectionEndDate($dominion)->diffInHours(Carbon::now()) + $fraction);
    }
}
