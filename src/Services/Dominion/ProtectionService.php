<?php

namespace OpenDominion\Services\Dominion;

use Carbon\Carbon;
use OpenDominion\Models\Dominion;

class ProtectionService
{
    public const PROTECTION_DURATION_IN_HOURS = 72; // todo: move to config?
    public const WAIT_PERIOD_DURATION_IN_HOURS = 24;

    /**
     * Returns the Dominion's 'under protection' start date.
     *
     * @param Dominion $dominion
     * @return Carbon
     */
    public function getProtectionStartDate(Dominion $dominion): Carbon
    {
        $roundStartDate = Carbon::parse($dominion->round->start_date);

        return $roundStartDate;
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
     * Returns the Dominion's 'wait period' end date.
     *
     * @param Dominion $dominion
     * @return Carbon
     */
    public function getWaitPeriodEndDate(Dominion $dominion): Carbon
    {
        $protectionEndDate = $this->getProtectionEndDate($dominion);

        return $protectionEndDate->addHours(self::WAIT_PERIOD_DURATION_IN_HOURS);
    }

    /**
     * Returns whether this Dominion instance is able to leave protection.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function canLeaveProtection(Dominion $dominion): bool
    {
        $protectionEndDate = $this->getProtectionEndDate($dominion);
        $waitPeriodEndDate = $this->getWaitPeriodEndDate($dominion);

        if ($protectionEndDate < now() && $waitPeriodEndDate > now()) {
            return false;
        }

        return true;
    }

    /**
     * Returns whether this Dominion instance is under protection.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function isUnderProtection(Dominion $dominion): bool
    {
        $protectionEndDate = $this->getProtectionEndDate($dominion);

        return ($dominion->protection_ticks_remaining > 0) || ($protectionEndDate >= now());
    }

    /**
     * Returns the hours the Dominion is still under protection for.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getUnderProtectionHoursLeft(Dominion $dominion): float
    {
        $now = now();
        $protectionEndDate = $this->getProtectionEndDate($dominion);

        if ($protectionEndDate < $now) {
            return 0;
        }

        $diffInHours = $protectionEndDate->diffInHours($now);

        $minutes = (int)$now->format('i');
        $seconds = (int)$now->format('s');

        $fraction = (1 - ((($minutes * 60) + $seconds) / 3600));

        return min(
            ($diffInHours + $fraction),
            static::PROTECTION_DURATION_IN_HOURS
        );
    }
}
