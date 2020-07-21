<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use LogicException;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\RoundWonder;
use OpenDominion\Models\Wonder;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\WonderService;
use OpenDominion\Traits\DominionGuardsTrait;

class WonderActionService
{
    use DominionGuardsTrait;

    /** @var WonderService */
    protected $wonderService;

    /**
     * WonderActionService constructor.
     *
     * @param WonderService $wonderService
     */
    public function __construct(WonderService $wonderService)
    {
        $this->wonderService = $wonderService;
    }

    /**
     * Attacks target $wonder from $dominion.
     *
     * @param Dominion $dominion
     * @param RoundWonder $wonder
     * @return array
     * @throws LogicException
     * @throws GameException
     */
    public function attack(Dominion $dominion, RoundWonder $wonder): array
    {
        $this->guardLockedDominion($dominion);

        return [
            'message' => sprintf(
                'You have attacked %s.',
                $wonder->wonder->name
            )
        ];
    }

    /**
     * Casts a spell at target $wonder from $dominion.
     *
     * @param Dominion $dominion
     * @param RoundWonder $wonder
     * @return array
     * @throws LogicException
     * @throws GameException
     */
    public function spell(Dominion $dominion, RoundWonder $wonder): array
    {
        $this->guardLockedDominion($dominion);

        return [
            'message' => sprintf(
                'You have cast a spell at %s.',
                $wonder->wonder->name
            )
        ];
    }
}
