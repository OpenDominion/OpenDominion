<?php

namespace OpenDominion\Services\Dominion;

use Carbon\Carbon;
use OpenDominion\Models\Dominion;

class GuardService
{
    public const GUARD_JOIN_WAIT_IN_HOURS = 24;

    public const GUARD_LEAVE_WAIT_IN_HOURS = 48;

    /**
     * Returns the Dominion's royal guard join time.
     *
     * @param Dominion $dominion
     * @return Carbon
     */
    protected function getRoyalGuardJoinDate(Dominion $dominion): Carbon
    {
        $joinDate = Carbon::parse($dominion->royal_guard);
        return Carbon::parse($joinDate->format('Y-m-d H:00:00'));
    }

    /**
     * Returns the Dominion's elite guard join time.
     *
     * @param Dominion $dominion
     * @return Carbon
     */
    protected function getEliteGuardJoinDate(Dominion $dominion): Carbon
    {
        $joinDate = Carbon::parse($dominion->elite_guard);
        return Carbon::parse($joinDate->format('Y-m-d H:00:00'));
    }

    /**
     * Returns the Dominion's royal guard application status.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function isRoyalGuardApplicant(Dominion $dominion): bool
    {
        if ($dominion->royal_guard != null) {
            $modifiedJoinDate = $this->getRoyalGuardJoinDate($dominion);
            if ($modifiedJoinDate > now())
                return true;
        }
        return false;
    }

    /**
     * Returns the Dominion's elite guard application status.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function isEliteGuardApplicant(Dominion $dominion): bool
    {
        if ($dominion->elite_guard != null) {
            $modifiedJoinDate = $this->getEliteGuardJoinDate($dominion);
            if ($modifiedJoinDate > now())
                return true;
        }
        return false;
    }

    /**
     * Returns the Dominion's royal guard membership status.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function isRoyalGuardMember(Dominion $dominion): bool
    {
        if ($dominion->royal_guard != null) {
            $modifiedJoinDate = $this->getRoyalGuardJoinDate($dominion);
            if ($modifiedJoinDate <= now())
                return true;
        }
        return false;
    }

    /**
     * Returns the Dominion's elite guard membership status.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function isEliteGuardMember(Dominion $dominion): bool
    {
        if ($dominion->elite_guard != null) {
            $modifiedJoinDate = $this->getEliteGuardJoinDate($dominion);
            if ($modifiedJoinDate <= now())
                return true;
        }
        return false;
    }

    /**
     * Returns the number of hours remaining before Dominion joins the royal guard.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getHoursBeforeRoyalGuardMember(Dominion $dominion): int
    {
        if ($this->isRoyalGuardApplicant($dominion)) {
            $modifiedJoinDate = $this->getRoyalGuardJoinDate($dominion);
            return $modifiedJoinDate->diffInHours(Carbon::parse(now()->format('Y-m-d H:00:00')));
        }
        return 0;
    }

    /**
     * Returns the number of hours remaining before Dominion joins the royal guard.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getHoursBeforeEliteGuardMember(Dominion $dominion): int
    {
        if ($this->isEliteGuardApplicant($dominion)) {
            $modifiedJoinDate = $this->getEliteGuardJoinDate($dominion);
            return $modifiedJoinDate->diffInHours(Carbon::parse(now()->format('Y-m-d H:00:00')));
        }
        return 0;
    }

    /**
     * Returns the number of hours remaining before Dominion can leave the royal guard.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getHoursBeforeLeaveRoyalGuard(Dominion $dominion): int
    {
        if ($this->isRoyalGuardMember($dominion)) {
            $modifiedJoinDate = $this->getRoyalGuardJoinDate($dominion);
            $leaveDate = $modifiedJoinDate->add(self::GUARD_LEAVE_WAIT_IN_HOURS);
            return $leaveDate->diffInHours(now()->format('Y-m-d H:00:00'));
        }
        return 0;
    }

    /**
     * Returns the number of hours remaining before Dominion can leave the elite guard.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getHoursBeforeLeaveEliteGuard(Dominion $dominion): int
    {
        if ($this->isEliteGuardMember($dominion)) {
            $modifiedJoinDate = $this->getEliteGuardJoinDate($dominion);
            $leaveDate = $modifiedJoinDate->add(self::GUARD_LEAVE_WAIT_IN_HOURS);
            return $leaveDate->diffInHours(now()->format('Y-m-d H:00:00'));
        }
        return 0;
    }

    /**
     * Sets the Dominion's royal guard join time.
     * 
     * @param Dominion $dominion
     * @return void
     */
    public function setRoyalGuardApplication(Dominion $dominion)
    {
        $dominion->royal_guard = now()->addHours(self::GUARD_JOIN_WAIT_IN_HOURS);
        $dominion->save();
    }

    /**
     * Sets the Dominion's elite guard join time.
     * 
     * @param Dominion $dominion
     * @return void
     */
    public function setEliteGuardApplication(Dominion $dominion)
    {
        $dominion->elite_guard = now()->addHours(self::GUARD_JOIN_WAIT_IN_HOURS);
        $dominion->save();
    }

    /**
     * Removes the Dominion's royal guard join time.
     * 
     * @param Dominion $dominion
     * @return void
     */
    public function leaveRoyalGuard(Dominion $dominion)
    {
        $dominion->royal_guard = null;
        $dominion->save();
    }

    /**
     * Removes the Dominion's elite guard join time.
     * 
     * @param Dominion $dominion
     * @return void
     */
    public function leaveEliteGuard(Dominion $dominion)
    {
        $dominion->elite_guard = null;
        $dominion->save();
    }
}
