<?php

namespace OpenDominion\Services\Dominion;

use Carbon\Carbon;
use OpenDominion\Models\Dominion;

class ProtectionService
{
    public const PROTECTION_DURATION_IN_HOURS = 72; // todo: move to config?
    public const WAIT_PERIOD_DURATION_IN_HOURS = 24;

    /**
     * Returns whether this Dominion instance is able to leave protection.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function canLeaveProtection(Dominion $dominion): bool
    {
        $waitPeriodEndDate = $dominion->round->start_date->addHours(self::WAIT_PERIOD_DURATION_IN_HOURS);

        if ($dominion->round->start_date < now() && $waitPeriodEndDate > now()) {
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
        return ($dominion->protection_ticks_remaining > 0) || ($dominion->round->start_date >= now());
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
        if ($dominion->round->start_date < $now) {
            return 0;
        }

        $diffInHours = $dominion->round->start_date->diffInHours($now);

        $minutes = (int)$now->format('i');
        $seconds = (int)$now->format('s');

        $fraction = (1 - ((($minutes * 60) + $seconds) / 3600));

        return round($diffInHours + $fraction, 2);
    }
}
