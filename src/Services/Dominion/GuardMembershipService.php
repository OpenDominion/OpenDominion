<?php

namespace OpenDominion\Services\Dominion;

use Carbon\Carbon;
use OpenDominion\Models\Dominion;

class GuardMembershipService
{
    // todo: use these constants in views/messages
    public const GUARD_HOURS_AFTER_ROUND_START = 24 * 2;
    public const GUARD_JOIN_DELAY_IN_HOURS = 24;
    public const GUARD_LEAVE_WAIT_IN_HOURS = 48;

    public const BLACK_GUARD_LEAVE_WAIT_IN_HOURS = 48;
    public const BLACK_GUARD_LEAVE_DELAY_IN_HOURS = 12;

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
        if ($dominion->round->hasStarted() && now()->diffInHours($dominion->round->start_date) < self::GUARD_HOURS_AFTER_ROUND_START) {
            return false;
        }

        return true;
    }

    /**
     * Returns the Dominion's royal guard application status.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function isRoyalGuardApplicant(Dominion $dominion): bool
    {
        if ($dominion->royal_guard_active_at !== null && $dominion->royal_guard_active_at > now()) {
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
        if ($dominion->elite_guard_active_at !== null && $dominion->elite_guard_active_at > now()) {
            return true;
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
        if ($dominion->black_guard_active_at !== null && $dominion->black_guard_active_at > now()) {
            return true;
        }

        return false;
    }

    /**
     * Returns the Dominion's black guard leave status.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function isLeavingBlackGuard(Dominion $dominion): bool
    {
        if ($dominion->black_guard_inactive_at !== null && $dominion->black_guard_inactive_at > now()) {
            return true;
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
        if ($dominion->royal_guard_active_at !== null && $dominion->royal_guard_active_at <= now()) {
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
        if ($dominion->elite_guard_active_at !== null && $dominion->elite_guard_active_at <= now()) {
            return true;
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
            if ($dominion->black_guard_inactive_at == null) {
                if ($dominion->black_guard_active_at <= now()) {
                    return true;
                }
            } else {
                if ($dominion->black_guard_inactive_at > now()) {
                    return true;
                }
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
        if ($dominion->royal_guard_active_at == null || !$this->isRoyalGuardApplicant($dominion)) {
            return 0;
        }

        return $dominion->royal_guard_active_at->diffInHours(now()->startOfHour());
    }

    /**
     * Returns the number of hours remaining before Dominion joins the elite guard.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getHoursBeforeEliteGuardMember(Dominion $dominion): int
    {
        if ($dominion->elite_guard_active_at == null || !$this->isEliteGuardApplicant($dominion)) {
            return 0;
        }

        return $dominion->elite_guard_active_at->diffInHours(now()->startOfHour());
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

        return $dominion->black_guard_active_at->diffInHours(now()->startOfHour());
    }

    /**
     * Returns the number of hours remaining before Dominion can leave the royal guard.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getHoursBeforeLeaveRoyalGuard(Dominion $dominion): int
    {
        if ($dominion->royal_guard_active_at == null || !$this->isRoyalGuardMember($dominion)) {
            return 0;
        }

        $leaveDate = $dominion->royal_guard_active_at->addHours(self::GUARD_LEAVE_WAIT_IN_HOURS);

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
        if ($dominion->elite_guard_active_at == null || !$this->isEliteGuardMember($dominion)) {
            return 0;
        }

        $leaveDate = $dominion->elite_guard_active_at->addHours(self::GUARD_LEAVE_WAIT_IN_HOURS);

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
        if ($dominion->black_guard_active_at == null || !$this->isBlackGuardMember($dominion)) {
            return 0;
        }

        $leaveDate = $dominion->black_guard_active_at->addHours(self::BLACK_GUARD_LEAVE_WAIT_IN_HOURS);

        if ($leaveDate > now()->startOfHour()) {
            return $leaveDate->diffInHours(now()->startOfHour());
        }

        return 0;
    }

    /**
     * Returns the number of hours remaining before Dominion leaving the black guard.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getHoursBeforeLeavingBlackGuard(Dominion $dominion): int
    {
        if ($dominion->black_guard_inactive_at == null || !$this->isBlackGuardMember($dominion)) {
            return 0;
        }

        $leaveDate = $dominion->black_guard_inactive_at;

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
        $dominion->royal_guard_active_at = now()->startOfHour()->addHours(self::GUARD_JOIN_DELAY_IN_HOURS);
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
        $dominion->elite_guard_active_at = now()->startOfHour()->addHours(self::GUARD_JOIN_DELAY_IN_HOURS);
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
        $dominion->black_guard_active_at = now()->startOfHour()->addHours(self::GUARD_JOIN_DELAY_IN_HOURS);
        $dominion->black_guard_inactive_at = null;
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
        if ($dominion->black_guard_active_at > now()) {
            $dominion->black_guard_active_at = null;
        } else {
            $dominion->black_guard_inactive_at = now()->startOfHour()->addHours(self::BLACK_GUARD_LEAVE_DELAY_IN_HOURS);
        }
        $dominion->save(['event' => HistoryService::EVENT_ACTION_LEAVE_BLACK_GUARD]);
    }

    /**
     * Removes the Dominion's black guard leave time.
     *
     * @param Dominion $dominion
     * @return void
     */
    public function cancelLeaveBlackGuard(Dominion $dominion): void
    {
        $dominion->black_guard_inactive_at = null;
        $dominion->save(['event' => HistoryService::EVENT_ACTION_CANCEL_LEAVE_BLACK_GUARD]);
    }

    /**
     * Resets the Dominion's black guard leave time.
     *
     * @param Dominion $dominion
     * @return void
     */
    public function checkLeaveApplication(Dominion $dominion): void
    {
        if ($this->isLeavingBlackGuard($dominion)) {
            $this->leaveBlackGuard($dominion);
        }
    }
}
