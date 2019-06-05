<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\GuardService;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;

class GuardActionService
{
    use DominionGuardsTrait;

    /** @var GuardService */
    protected $guardService;

    /**
     * GuardActionService constructor.
     *
     * @param GuardService $guardService
     */
    public function __construct(GuardService $guardService)
    {
        $this->guardService = $guardService;
    }

    /**
     * Starts royal guard application for a Dominion.
     *
     * @param Dominion $dominion
     * @return array
     * @throws RuntimeException
     */
    public function joinRoyalGuard(Dominion $dominion): array
    {
        if ($this->guardService->isRoyalGuardMember($dominion)) {
            throw new RuntimeException('You are already a member of the Emperor\'s Royal Guard.');
        } elseif ($this->guardService->isRoyalGuardApplicant($dominion)) {
            throw new RuntimeException('You have already applied to join the Emperor\'s Royal Guard.');
        }

        $this->guardService->joinRoyalGuard($dominion);

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
     * @throws RuntimeException
     */
    public function joinEliteGuard(Dominion $dominion): array
    {
        if($this->guardService->isRoyalGuardMember($dominion)) {
            throw new RuntimeException('You must already be a member of the Emperor\'s Royal Guard.');
        } elseif ($this->guardService->isEliteGuardMember($dominion)) {
            throw new RuntimeException('You are already a member of the Emperor\'s Elite Guard.');
        } elseif ($this->guardService->isEliteGuardApplicant($dominion)) {
            throw new RuntimeException('You have already applied to join the Emperor\'s Elite Guard.');
        }

        $this->guardService->joinEliteGuard($dominion);

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
     * @throws RuntimeException
     */
    public function leaveRoyalGuard(Dominion $dominion): array
    {
        if ($this->guardService->isEliteGuardApplicant($dominion)) {
            throw new RuntimeException('You must first cancel your Emperor\'s Elite Guard application.');
        } elseif ($this->guardService->isEliteGuardMember($dominion)) {
            throw new RuntimeException('You must first leave the Emperor\'s Elite Guard.');
        } elseif (!$this->guardService->isRoyalGuardApplicant($dominion) && !$this->guardService->isRoyalGuardMember($dominion)) {
            throw new RuntimeException('You are not a member of the Emperor\'s Royal Guard.');
        }

        if ($this->guardService->isRoyalGuardApplicant($dominion)) {
            $message = 'You have canceled your Emperor\'s Royal Guard application.';
        } else {
            $message = 'You have left the Emperor\'s Royal Guard.';
        }

        $this->guardService->leaveRoyalGuard($dominion);

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
     * @throws RuntimeException
     */
    public function leaveEliteGuard(Dominion $dominion): array
    {
        if (!$this->guardService->isEliteGuardApplicant($dominion) && !$this->guardService->isEliteGuardMember($dominion)) {
            throw new RuntimeException('You are not a member of the Emperor\'s Elite Guard.');
        }

        if ($this->guardService->isEliteGuardApplicant($dominion)) {
            $message = 'You have canceled your Emperor\'s Elite Guard application.';
        } else {
            $message = 'You have left the Emperor\'s Elite Guard.';
        }

        $this->guardService->leaveEliteGuard($dominion);

        return [
            'message' => $message,
            'data' => []
        ];
    }
}
