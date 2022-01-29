<?php

namespace OpenDominion\Services\Dominion;

use Carbon\Carbon;
use OpenDominion\Models\Dominion;

class GuardMembershipService
{
    // todo: use these constants in views/messages
    public const GUARD_HOURS_AFTER_ROUND_START = 24 * 3;
    public const GUARD_JOIN_WAIT_IN_HOURS = 24;
    public const GUARD_LEAVE_WAIT_IN_HOURS = 48;

    public const ROYAL_GUARD_RANGE = 0.6;
    public const ELITE_GUARD_RANGE = 0.75;

    /**
     * Returns guard availability for a Dominion.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function canJoinGuards(Dominion $dominion): bool
    {
        /** @noinspection IfReturnReturnSimplificationInspection */
        if (now()->diffInHours($dominion->round->start_date) < self::GUARD_HOURS_AFTER_ROUND_START) {
            return false;
        }

        return true;
    }

    /**
     * Returns the Dominion's royal guard join time.
     *
     * @param Dominion $dominion
     * @return Carbon
     */
    protected function getRoyalGuardJoinDate(Dominion $dominion): Carbon
    {
        return Carbon::parse($dominion->royal_guard_active_at);
    }

    /**
     * Returns the Dominion's elite guard join time.
     *
     * @param Dominion $dominion
     * @return Carbon
     */
    protected function getEliteGuardJoinDate(Dominion $dominion): Carbon
    {
        return Carbon::parse($dominion->elite_guard_active_at);
    }

    /**
     * Returns the Dominion's black guard join time.
     *
     * @param Dominion $dominion
     * @return Carbon
     */
    protected function getBlackGuardJoinDate(Dominion $dominion): Carbon
    {
        return Carbon::parse($dominion->black_guard_active_at);
    }

    /**
     * Returns the Dominion's royal guard application status.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function isRoyalGuardApplicant(Dominion $dominion): bool
    {
        if ($dominion->royal_guard_active_at !== null) {
            $modifiedJoinDate = $this->getRoyalGuardJoinDate($dominion);

            if ($modifiedJoinDate > now()) {
                return true;
            }
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
        if ($dominion->elite_guard_active_at !== null) {
            $modifiedJoinDate = $this->getEliteGuardJoinDate($dominion);

            if ($modifiedJoinDate > now()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the Dominion's black guard application status.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function isBlackGuardApplicant(Dominion $dominion): bool
    {
        if ($dominion->black_guard_active_at !== null) {
            $modifiedJoinDate = $this->getBlackGuardJoinDate($dominion);

            if ($modifiedJoinDate > now()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the Dominion's guard membership status.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function isGuardMember(Dominion $dominion): bool
    {
        if ($dominion->royal_guard_active_at !== null) {
            $modifiedJoinDate = $this->getRoyalGuardJoinDate($dominion);

            if ($modifiedJoinDate <= now()) {
                return true;
            }
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
        return $this->isGuardMember($dominion) && !$this->isEliteGuardMember($dominion);
    }

    /**
     * Returns the Dominion's elite guard membership status.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function isEliteGuardMember(Dominion $dominion): bool
    {
        if ($dominion->elite_guard_active_at !== null) {
            $modifiedJoinDate = $this->getEliteGuardJoinDate($dominion);

            if ($modifiedJoinDate <= now()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the Dominion's black guard membership status.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function isBlackGuardMember(Dominion $dominion): bool
    {
        if ($dominion->black_guard_active_at !== null) {
            $modifiedJoinDate = $this->getBlackGuardJoinDate($dominion);

            if ($modifiedJoinDate <= now()) {
                return true;
            }
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
        if (!$this->isRoyalGuardApplicant($dominion)) {
            return 0;
        }

        $modifiedJoinDate = $this->getRoyalGuardJoinDate($dominion);

        return $modifiedJoinDate->diffInHours(now()->startOfHour());
    }

    /**
     * Returns the number of hours remaining before Dominion joins the elite guard.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getHoursBeforeEliteGuardMember(Dominion $dominion): int
    {
        if (!$this->isEliteGuardApplicant($dominion)) {
            return 0;
        }

        $modifiedJoinDate = $this->getEliteGuardJoinDate($dominion);

        return $modifiedJoinDate->diffInHours(now()->startOfHour());
    }

    /**
     * Returns the number of hours remaining before Dominion joins the black guard.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getHoursBeforeBlackGuardMember(Dominion $dominion): int
    {
        if (!$this->isBlackGuardApplicant($dominion)) {
            return 0;
        }

        $modifiedJoinDate = $this->getBlackGuardJoinDate($dominion);

        return $modifiedJoinDate->diffInHours(now()->startOfHour());
    }

    /**
     * Returns the number of hours remaining before Dominion can leave the royal guard.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getHoursBeforeLeaveRoyalGuard(Dominion $dominion): int
    {
        if (!$this->isRoyalGuardMember($dominion)) {
            return 0;
        }

        $modifiedJoinDate = $this->getRoyalGuardJoinDate($dominion);
        $leaveDate = $modifiedJoinDate->addHours(self::GUARD_LEAVE_WAIT_IN_HOURS);

        if ($leaveDate > now()->startOfHour()) {
            return $leaveDate->diffInHours(now()->startOfHour());
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
        if (!$this->isEliteGuardMember($dominion)) {
            return 0;
        }

        $modifiedJoinDate = $this->getEliteGuardJoinDate($dominion);
        $leaveDate = $modifiedJoinDate->addHours(self::GUARD_LEAVE_WAIT_IN_HOURS);

        if ($leaveDate > now()->startOfHour()) {
            return $leaveDate->diffInHours(now()->startOfHour());
        }

        return 0;
    }

    /**
     * Returns the number of hours remaining before Dominion can leave the black guard.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getHoursBeforeLeaveBlackGuard(Dominion $dominion): int
    {
        if (!$this->isBlackGuardMember($dominion)) {
            return 0;
        }

        $modifiedJoinDate = $this->getBlackGuardJoinDate($dominion);
        $leaveDate = $modifiedJoinDate->addHours(self::GUARD_LEAVE_WAIT_IN_HOURS);

        if ($leaveDate > now()->startOfHour()) {
            return $leaveDate->diffInHours(now()->startOfHour());
        }

        return 0;
    }

    /**
     * Sets the Dominion's royal guard join time.
     *
     * @param Dominion $dominion
     * @return void
     */
    public function joinRoyalGuard(Dominion $dominion): void
    {
        $dominion->royal_guard_active_at = now()->startOfHour()->addHours(self::GUARD_JOIN_WAIT_IN_HOURS);
        $dominion->save(['event' => HistoryService::EVENT_ACTION_JOIN_ROYAL_GUARD]);
    }

    /**
     * Sets the Dominion's elite guard join time.
     *
     * @param Dominion $dominion
     * @return void
     */
    public function joinEliteGuard(Dominion $dominion): void
    {
        $dominion->elite_guard_active_at = now()->startOfHour()->addHours(self::GUARD_JOIN_WAIT_IN_HOURS);
        $dominion->save(['event' => HistoryService::EVENT_ACTION_JOIN_ELITE_GUARD]);
    }

    /**
     * Sets the Dominion's black guard join time.
     *
     * @param Dominion $dominion
     * @return void
     */
    public function joinBlackGuard(Dominion $dominion): void
    {
        $dominion->black_guard_active_at = now()->startOfHour()->addHours(self::GUARD_JOIN_WAIT_IN_HOURS);
        $dominion->save(['event' => HistoryService::EVENT_ACTION_JOIN_BLACK_GUARD]);
    }

    /**
     * Removes the Dominion's royal guard join time.
     *
     * @param Dominion $dominion
     * @return void
     */
    public function leaveRoyalGuard(Dominion $dominion): void
    {
        $dominion->royal_guard_active_at = null;
        $dominion->save(['event' => HistoryService::EVENT_ACTION_LEAVE_ROYAL_GUARD]);
    }

    /**
     * Removes the Dominion's elite guard join time.
     *
     * @param Dominion $dominion
     * @return void
     */
    public function leaveEliteGuard(Dominion $dominion): void
    {
        $dominion->elite_guard_active_at = null;
        $dominion->save(['event' => HistoryService::EVENT_ACTION_LEAVE_ELITE_GUARD]);
    }

    /**
     * Removes the Dominion's black guard join time.
     *
     * @param Dominion $dominion
     * @return void
     */
    public function leaveBlackGuard(Dominion $dominion): void
    {
        $dominion->black_guard_active_at = null;
        $dominion->save(['event' => HistoryService::EVENT_ACTION_LEAVE_BLACK_GUARD]);
    }
}
