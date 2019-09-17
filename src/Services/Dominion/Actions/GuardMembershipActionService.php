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
        if (!$this->guardMembershipService->canJoinGuards($dominion)) {
            throw new GameException('You cannot join the Emperor\'s Royal Guard for the first five days of the round.');
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
     * Leaves the royal guard or cancels an application for a Dominion.
     *
     * @param Dominion $dominion
     * @return array
     * @throws GameException
     */
    public function leaveRoyalGuard(Dominion $dominion): array
    {
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
}
