<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Realm\HistoryService;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;

class GovernmentActionService
{
    use DominionGuardsTrait;

    /** @var GovernmentService */
    protected $governmentService;

    /**
     * GovernmentActionService constructor.
     *
     * @param GovernmentService $governmentService
     */
    public function __construct(GovernmentService $governmentService)
    {
        $this->governmentService = $governmentService;
    }

    /**
     * Casts a Dominion's vote for monarch.
     *
     * @param Dominion $dominion
     * @param int $monarch_id
     * @throws RuntimeException
     */
    public function voteForMonarch(Dominion $dominion, ?int $monarch_id)
    {
        $monarch = Dominion::find($monarch_id);
        if ($monarch == null) {
            throw new RuntimeException('Dominion not found.');
        }
        if ($dominion->realm != $monarch->realm) {
            throw new RuntimeException('You cannot vote for a monarch outside of your realm.');
        }

        $dominion->monarch_dominion_id = $monarch->id;
        $dominion->save();

        $this->governmentService->checkMonarchVotes($dominion->realm);
    }

    /**
     * Changes a Dominion's realm name.
     *
     * @param Dominion $dominion
     * @param string $name
     * @throws RuntimeException
     */
    public function changeRealmName(Dominion $dominion, string $name)
    {
        if (!$dominion->isMonarch()) {
            throw new GameException('Only the monarch can change the name of their realm.');
        }

        if (strlen($name) > 64) {
            throw new GameException('Realm names are limited to 64 characters.');
        }

        $dominion->realm->name = $name;
        $dominion->realm->save(['event' => HistoryService::EVENT_ACTION_CHANGED_NAME]);
    }
}
