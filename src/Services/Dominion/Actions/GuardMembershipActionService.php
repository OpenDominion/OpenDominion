<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Traits\DominionGuardsTrait;

class GuardMembershipActionService
{
    use DominionGuardsTrait;

    /** @var GuardMembershipService */
    protected $guardMembershipService;

    /**
     * GuardMembershipActionService constructor.
     *
     * @param GuardMembershipService $guardMembershipService
     */
    public function __construct(GuardMembershipService $guardMembershipService)
    {
        $this->guardMembershipService = $guardMembershipService;
    }

    /**
     * Starts royal guard application for a Dominion.
     *
     * @param Dominion $dominion
     * @return array
     * @throws GameException
     */
    public function joinRoyalGuard(Dominion $dominion): array
    {
        $this->guardLockedDominion($dominion);

        if (!$this->guardMembershipService->canJoinGuards($dominion)) {
            throw new GameException('You cannot join the Emperor\'s Royal Guard for the first two days of the round.');
        }

        if ($this->guardMembershipService->isRoyalGuardMember($dominion)) {
            throw new GameException('You are already a member of the Emperor\'s Royal Guard.');
        }

        if ($this->guardMembershipService->isRoyalGuardApplicant($dominion)) {
            throw new GameException('You have already applied to join the Emperor\'s Royal Guard.');
        }

        $this->guardMembershipService->joinRoyalGuard($dominion);

        return [
            'message' => sprintf(
                'You have applied to join the Emperor\'s Royal Guard.'
            ),
            'data' => []
        ];
    }

    /**
     * Starts elite guard application for a Dominion.
     *
     * @param Dominion $dominion
     * @return array
     * @throws GameException
     */
    public function joinEliteGuard(Dominion $dominion): array
    {
        $this->guardLockedDominion($dominion);

        // todo: cannot join for first 5 days
        if (!$this->guardMembershipService->isRoyalGuardMember($dominion)) {
            throw new GameException('You must already be a member of the Emperor\'s Royal Guard.');
        }

        if ($this->guardMembershipService->isEliteGuardMember($dominion)) {
            throw new GameException('You are already a member of the Emperor\'s Elite Guard.');
        }

        if ($this->guardMembershipService->isEliteGuardApplicant($dominion)) {
            throw new GameException('You have already applied to join the Emperor\'s Elite Guard.');
        }

        $this->guardMembershipService->joinEliteGuard($dominion);

        return [
            'message' => sprintf(
                'You have applied to join the Emperor\'s Elite Guard.'
            ),
            'data' => []
        ];
    }

    /**
     * Starts black guard application for a Dominion.
     *
     * @param Dominion $dominion
     * @return array
     * @throws GameException
     */
    public function joinBlackGuard(Dominion $dominion): array
    {
        $this->guardLockedDominion($dominion);

        if ($this->guardMembershipService->isBlackGuardMember($dominion)) {
            throw new GameException('You are already a member of the Shadow League.');
        }

        if ($this->guardMembershipService->isBlackGuardApplicant($dominion)) {
            throw new GameException('You have already applied to join the Shadow League.');
        }

        $this->guardMembershipService->joinBlackGuard($dominion);

        return [
            'message' => sprintf(
                'You have applied to join the Shadow League.'
            ),
            'data' => []
        ];
    }

    /**
     * Leaves the royal guard or cancels an application for a Dominion.
     *
     * @param Dominion $dominion
     * @return array
     * @throws GameException
     */
    public function leaveRoyalGuard(Dominion $dominion): array
    {
        $this->guardLockedDominion($dominion);

        if ($this->guardMembershipService->getHoursBeforeLeaveRoyalGuard($dominion)) {
            throw new GameException('You cannot leave the Emperor\'s Royal Guard for 48 hours after joining.');
        }

        if ($this->guardMembershipService->isEliteGuardApplicant($dominion)) {
            throw new GameException('You must first cancel your Emperor\'s Elite Guard application.');
        }

        if ($this->guardMembershipService->isEliteGuardMember($dominion)) {
            throw new GameException('You must first leave the Emperor\'s Elite Guard.');
        }

        if (!$this->guardMembershipService->isRoyalGuardApplicant($dominion) && !$this->guardMembershipService->isRoyalGuardMember($dominion)) {
            throw new GameException('You are not a member of the Emperor\'s Royal Guard.');
        }

        if ($this->guardMembershipService->isRoyalGuardApplicant($dominion)) {
            $message = 'You have canceled your Emperor\'s Royal Guard application.';
        } else {
            $message = 'You have left the Emperor\'s Royal Guard.';
        }

        $this->guardMembershipService->leaveRoyalGuard($dominion);

        return [
            'message' => $message,
            'data' => []
        ];
    }

    /**
     * Leaves the elite guard or cancels an application for a Dominion.
     *
     * @param Dominion $dominion
     * @return array
     * @throws GameException
     */
    public function leaveEliteGuard(Dominion $dominion): array
    {
        $this->guardLockedDominion($dominion);

        if ($this->guardMembershipService->getHoursBeforeLeaveEliteGuard($dominion)) {
            throw new GameException('You cannot leave the Emperor\'s Elite Guard for 48 hours after joining.');
        }

        if (!$this->guardMembershipService->isEliteGuardApplicant($dominion) && !$this->guardMembershipService->isEliteGuardMember($dominion)) {
            throw new GameException('You are not a member of the Emperor\'s Elite Guard.');
        }

        if ($this->guardMembershipService->isEliteGuardApplicant($dominion)) {
            $message = 'You have canceled your Emperor\'s Elite Guard application.';
        } else {
            $message = 'You have left the Emperor\'s Elite Guard.';
        }

        $this->guardMembershipService->leaveEliteGuard($dominion);

        return [
            'message' => $message,
            'data' => []
        ];
    }

    /**
     * Leaves the black guard or cancels an application for a Dominion.
     *
     * @param Dominion $dominion
     * @return array
     * @throws GameException
     */
    public function leaveBlackGuard(Dominion $dominion): array
    {
        $this->guardLockedDominion($dominion);

        if ($this->guardMembershipService->getHoursBeforeLeaveBlackGuard($dominion)) {
            throw new GameException('You cannot leave the Shadow League for 48 hours after joining.');
        }

        if (!$this->guardMembershipService->isBlackGuardApplicant($dominion) && !$this->guardMembershipService->isBlackGuardMember($dominion)) {
            throw new GameException('You are not a member of the Shadow League.');
        }

        if ($this->guardMembershipService->isBlackGuardApplicant($dominion)) {
            $message = 'You have canceled your Shadow League application.';
        } else {
            $message = 'You will leave the Shadow League in 12 hours.';
        }

        $this->guardMembershipService->leaveBlackGuard($dominion);

        return [
            'message' => $message,
            'data' => []
        ];
    }

    /**
     * Cancels leaving the black guard for a Dominion.
     *
     * @param Dominion $dominion
     * @return array
     * @throws GameException
     */
    public function cancelLeaveBlackGuard(Dominion $dominion): array
    {
        $this->guardLockedDominion($dominion);

        if (!$this->guardMembershipService->isLeavingBlackGuard($dominion)) {
            throw new GameException('You are not leaving the Shadow League.');
        }

        $this->guardMembershipService->cancelLeaveBlackGuard($dominion);

        return [
            'message' => 'You will remain in the Shadow League.',
            'data' => []
        ];
    }
}
