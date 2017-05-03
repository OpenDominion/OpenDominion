<?php

namespace OpenDominion\Services;

use Carbon\Carbon;
use OpenDominion\Traits\DominionAwareTrait;

class DominionProtectionService
{
    use DominionAwareTrait;

    const PROTECTION_DURATION_IN_HOURS = 72; // todo: move to config?

    /**
     * Returns the Dominion's 'under protection' start date.
     *
     * @return Carbon
     */
    public function getProtectionStartDate()
    {
        $roundStartDate = Carbon::parse($this->dominion->round->start_date);
        $dominionCreatedDate = Carbon::parse($this->dominion->created_at);

        $protectionStartDate = (($dominionCreatedDate > $roundStartDate) ? $dominionCreatedDate : $roundStartDate);

        return Carbon::parse($protectionStartDate->format('Y-m-d H:00:00'));
    }

    /**
     * Returns the Dominion's 'under protection' end date.
     *
     * @return Carbon
     */
    public function getProtectionEndDate()
    {
        return $this->getProtectionStartDate()->addHours(self::PROTECTION_DURATION_IN_HOURS + 1);
    }

    /**
     * Returns whether this Dominion instance is under protection.
     *
     * @return bool
     */
    public function isUnderProtection()
    {
        return ($this->getProtectionEndDate() >= Carbon::now());
    }

    /**
     * Returns the hours the Dominion is still under protection for.
     *
     * @return int
     */
    public function getUnderProtectionHoursLeft()
    {
        if (!$this->isUnderProtection()) {
            return 0;
        }

        return $this->getProtectionEndDate()->diffInHours(Carbon::now());
    }
}
