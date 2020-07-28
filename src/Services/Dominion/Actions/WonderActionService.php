<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use LogicException;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\RoundWonder;
use OpenDominion\Models\Wonder;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\WonderService;
use OpenDominion\Traits\DominionGuardsTrait;

class WonderActionService
{
    use DominionGuardsTrait;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var ProtectionService */
    protected $protectionService;

    /** @var WonderService */
    protected $wonderService;

    /**
     * WonderActionService constructor.
     *
     * @param MilitaryCalculator $militaryCalculator
     * @param ProtectionService $protectionService
     * @param WonderService $wonderService
     */
    public function __construct(
        MilitaryCalculator $militaryCalculator,
        ProtectionService $protectionService,
        WonderService $wonderService
    ) {
        $this->militaryCalculator = $militaryCalculator;
        $this->protectionService = $protectionService;
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
    public function attack(Dominion $dominion, RoundWonder $wonder, array $units): array
    {
        $this->guardLockedDominion($dominion);

        DB::transaction(function () use ($dominion, $wonder, $units) {
            if ($dominion->round->hasOffensiveActionsDisabled()) {
                throw new GameException('Invasions have been disabled for the remainder of the round.');
            }

            if ($this->protectionService->isUnderProtection($dominion)) {
                throw new GameException('You cannot invade while under protection');
            }

            if ($dominion->round->id !== $wonder->round->id) {
                throw new GameException('Nice try, but you cannot invade cross-round');
            }

            // TODO: Check that wonder is neutral or in war-realm

            // TODO: Deal Damage
            $damageDealt = round($this->militaryCalculator->getOffensivePower($dominion, null, null, $units));
            $wonder->power -= $damageDealt;
            if ($wonder->power <= 0) {
                // TODO: Log damage
                if ($dominion->realm->wonder == null) {
                    // TODO: Determine who rebuilds the wonder
                    $wonder->realm_id = $dominion->realm_id;
                    $wonder->power = $wonder->wonder->power;
                    // TODO: GameEvent
                    // TODO: Queue notifications
                } else {
                    // Rebuild as neutral
                    $wonder->realm_id = null;
                    $wonder->power = $wonder->wonder->power;
                    // TODO: GameEvent
                    // TODO: Queue notifications
                }
            }

            // TODO: Increment stats
            // TODO: handleBoats
            // TODO: handleOffensiveCasualties
            // TODO: handleReturningUnits
            // TODO: handleMoraleChanges

            $dominion->save(); // TODO: event => historyservice
            $wonder->save(); // TODO: event => historyservice
        });

        // $this->notificationService->sendNotifications($target, 'irregular_dominion');

        $message = sprintf(
            'You have attacked %s.',
            $wonder->wonder->name
        );

        return [
            'message' => $message,
            'alert-type' => 'success',
            'redirect' => route('dominion.wonders')
            //'redirect' => route('dominion.event', [])
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
