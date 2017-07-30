<?php

namespace OpenDominion\Contracts\Services\Dominion;

use Carbon\Carbon;
use OpenDominion\Models\Dominion;

interface ProtectionService
{
    /**
     * Returns the Dominion's 'under protection' start date.
     *
     * @return Carbon
     */
    public function getProtectionStartDate(Dominion $dominion);

    /**
     * Returns the Dominion's 'under protection' end date.
     *
     * @return Carbon
     */
    public function getProtectionEndDate(Dominion $dominion);

    /**
     * Returns whether this Dominion instance is under protection.
     *
     * @return bool
     */
    public function isUnderProtection(Dominion $dominion);

    /**
     * Returns the hours the Dominion is still under protection for.
     *
     * @return float
     */
    public function getUnderProtectionHoursLeft(Dominion $dominion);
}
