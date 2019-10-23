<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use LogicException;
use OpenDominion\Calculators\Dominion\Actions\TechCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionTech;
use OpenDominion\Models\Tech;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Traits\DominionGuardsTrait;

class TechActionService
{
    use DominionGuardsTrait;

    /** @var TechCalculator */
    protected $techCalculator;

    /**
     * TechActionService constructor.
     *
     * @param TechCalculator $techCalculator
     */
    public function __construct(TechCalculator $techCalculator)
    {
        $this->techCalculator = $techCalculator;
    }

    /**
     * Does a tech unlock action for a Dominion.
     *
     * @param Dominion $dominion
     * @param string $key
     * @return array
     * @throws LogicException
     * @throws GameException
     */
    public function unlock(Dominion $dominion, string $key): array
    {
        $this->guardLockedDominion($dominion);

        // Get the tech information
        $techToUnlock = Tech::where('key', $key)->first();
        if ($techToUnlock == null) {
            throw new LogicException('Failed to find tech ' . $key);
        }

        // Check prerequisites
        if (!$this->techCalculator->hasPrerequisites($dominion, $techToUnlock)) {
            throw new GameException('You do not meet the requirements to unlock this tech.');
        }

        // Check research point
        $techCost = $this->techCalculator->getTechCost($dominion);
        if ($dominion->resource_tech < $techCost) {
            throw new GameException(sprintf(
                'You do not have %s research points to unlock this tech.',
                number_format($techCost)
            ));
        }

        DB::transaction(function () use ($dominion, $techToUnlock, $techCost) {
            DominionTech::create([
                'dominion_id' => $dominion->id,
                'tech_id' => $techToUnlock->id
            ]);

            $dominion->resource_tech -= $techCost;
            $dominion->save([
                'event' => HistoryService::EVENT_ACTION_TECH,
                'action' => $techToUnlock->key
            ]);
        });

        return [
            'message' => sprintf(
                'You have unlocked %s.',
                $techToUnlock->name
            )
        ];
    }
}
