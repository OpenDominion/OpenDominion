<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use LogicException;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Realm;
use OpenDominion\Models\RoundWonder;
use OpenDominion\Models\Wonder;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\InvasionService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\WonderService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Traits\DominionGuardsTrait;

class WonderActionService
{
    use DominionGuardsTrait;

    /**
     * @var float Base percentage of offensive casualties
     */
    protected const CASUALTIES_BASE_PERCENTAGE = 5;

    /** @var InvasionService */
    protected $invasionService;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var NotificationService */
    protected $notificationService;

    /** @var ProtectionService */
    protected $protectionService;

    /** @var QueueService */
    protected $queueService;

    /** @var WonderService */
    protected $wonderService;

    /** @var array Attack result array. todo: Should probably be refactored later to its own class */
    protected $attackResult = [
        'attacker' => [
            'unitsLost' => [],
            'unitsSent' => [],
        ],
        'wonder' => [
            'currentRealmId' => null,
            'neutral' => null,
            'power' => null,
            'victorRealmId' => null,
        ],
    ];

    // todo: refactor
    /** @var GameEvent */
    protected $attackEvent;

    /**
     * WonderActionService constructor.
     *
     * @param InvasionService $invasionService
     * @param MilitaryCalculator $militaryCalculator
     * @param ProtectionService $protectionService
     * @param QueueService $queueService
     * @param WonderService $wonderService
     */
    public function __construct(
        InvasionService $invasionService,
        MilitaryCalculator $militaryCalculator,
        NotificationService $notificationService,
        ProtectionService $protectionService,
        QueueService $queueService,
        WonderService $wonderService
    ) {
        $this->invasionService = $invasionService;
        $this->militaryCalculator = $militaryCalculator;
        $this->notificationService = $notificationService;
        $this->protectionService = $protectionService;
        $this->queueService = $queueService;
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
                throw new GameException('Attacks have been disabled for the remainder of the round.');
            }

            if ($this->protectionService->isUnderProtection($dominion)) {
                throw new GameException('You cannot attack while under protection');
            }

            if ($dominion->round->id !== $wonder->round->id) {
                throw new GameException('Nice try, but you cannot attack cross-round');
            }

            $currentRealm = $wonder->realm;
            $this->attackResult['wonder']['currentRealmId'] = $wonder->realm_id;
            $this->attackResult['wonder']['neutral'] = ($wonder->realm_id == null);
            // TODO: Check that wonder is neutral or in war-realm

            // Sanitize input
            $units = array_map('intval', array_filter($units));

            if (!$this->invasionService->hasAnyOP($dominion, $units)) {
                throw new GameException('You need to send at least some units');
            }

            if (!$this->invasionService->allUnitsHaveOP($dominion, $units)) {
                throw new GameException('You cannot send units that have no OP');
            }

            if (!$this->invasionService->hasEnoughUnitsAtHome($dominion, $units)) {
                throw new GameException('You don\'t have enough units at home to send this many units');
            }

            if (!$this->invasionService->hasEnoughBoats($dominion, $units)) {
                throw new GameException('You do not have enough boats to send this many units');
            }

            if (!$this->invasionService->hasEnoughMorale($dominion)) {
                throw new GameException('You do not have enough morale to attack a wonder');
            }

            foreach($units as $amount) {
                if($amount < 0) {
                    throw new GameException('Attack was canceled due to bad input.');
                }
            }

            $damageDealt = round($this->militaryCalculator->getOffensivePower($dominion, null, null, $units));
            $wonder->power -= $damageDealt;
            // TODO: Log damage

            $this->attackResult['attacker']['op'] = $damageDealt;
            $this->attackResult['wonder']['power'] = $wonder->power;

            $this->handleBoats($dominion, $units);
            $survivingUnits = $this->handleCasualties($dominion, $units);
            $this->handleReturningUnits($dominion, $survivingUnits);

            $this->attackResult['attacker']['unitsSent'] = $units;

            $dominion->morale -= 5;
            // TODO: Increment stats

            $this->attackEvent = GameEvent::create([
                'round_id' => $dominion->round->id,
                'source_type' => Dominion::class,
                'source_id' => $dominion->id,
                'target_type' => RoundWonder::class,
                'target_id' => $wonder->id,
                'type' => 'wonder_attacked',
                'data' => $this->attackResult
            ]);

            if ($wonder->power <= 0) {
                if ($dominion->realm->wonders->isEmpty()) {
                    // TODO: Determine who rebuilds the wonder
                    $victorRealm = $dominion->realm;
                }

                // TODO: Calculate new power
                $wonder->realm_id = $victorRealm->id;
                $wonder->power = $wonder->wonder->power;
                $this->attackResult['wonder']['power'] = $wonder->power;
                $this->attackResult['wonder']['victorRealmId'] = $victorRealm->id;

                GameEvent::create([
                    'round_id' => $dominion->round->id,
                    'source_type' => RoundWonder::class,
                    'source_id' => $wonder->id,
                    'target_type' => Realm::class,
                    'target_id' => $wonder->realm_id,
                    'type' => 'wonder_destroyed',
                    'data' => $this->attackResult['wonder']
                ]);

                if ($victorRealm !== null) {
                    // Queue friendly notifications
                    foreach ($victorRealm->dominions as $friendlyDominion) {
                        $this->notificationService
                            ->queueNotification('wonder_rebuilt', [
                                'wonderId' => $wonder->wonder->id
                            ])
                            ->sendNotifications($friendlyDominion, 'irregular_realm');;
                    }
                }

                if ($currentRealm !== null) {
                    // Queue hostile notifications
                    foreach ($currentRealm->dominions as $hostileDominion) {
                        $this->notificationService
                            ->queueNotification('wonder_destroyed', [
                                'attackerRealmId' => $dominion->realm->id,
                                'wonderId' => $wonder->wonder->id
                            ])
                            ->sendNotifications($hostileDominion, 'irregular_realm');;
                    }
                }
            }

            if ($currentRealm !== null) {
                // Queue hostile notifications
                foreach ($currentRealm->dominions as $hostileDominion) {
                    $this->notificationService
                        ->queueNotification('wonder_attacked', [
                            '_routeParams' => [(string)$this->attackEvent->id],
                            'attackerDominionId' => $dominion->id,
                            'wonderId' => $wonder->wonder->id
                        ])
                        ->sendNotifications($hostileDominion, 'irregular_realm');;
                }
            }

            $dominion->save(); // TODO: event => historyservice
            $wonder->save(); // TODO: event => historyservice
        });

        $message = sprintf(
            'Your army has attacked the %s!', // todo: and it was destroyed!
            $wonder->wonder->name
        );

        return [
            'message' => $message,
            'alert-type' => 'success',
            'redirect' => route('dominion.event', [$this->attackEvent->id])
        ];
    }

    /**
     * Handles the returning boats.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param array $units
     */
    protected function handleBoats(Dominion $dominion, array $units): void
    {
        $unitsThatNeedsBoatsByReturnHours = [];
        // Calculate boats sent and attacker sinking perk
        foreach ($dominion->race->units as $unit) {
            if (!isset($units[$unit->slot]) || ((int)$units[$unit->slot] === 0)) {
                continue;
            }

            if ($unit->need_boat) {
                $hours = $this->invasionService->getUnitReturnHoursForSlot($dominion, $unit->slot);

                if (!isset($unitsThatNeedsBoatsByReturnHours[$hours])) {
                    $unitsThatNeedsBoatsByReturnHours[$hours] = 0;
                }

                $unitsThatNeedsBoatsByReturnHours[$hours] += (int)$units[$unit->slot];
            }
        }

        // Queue returning boats
        foreach ($unitsThatNeedsBoatsByReturnHours as $hours => $amountUnits) {
            $boatsByReturnHourGroup = (int)floor($amountUnits / $dominion->race->getBoatCapacity());

            $dominion->resource_boats -= $boatsByReturnHourGroup;

            $this->queueService->queueResources(
                'invasion',
                $dominion,
                ['resource_boats' => $boatsByReturnHourGroup],
                $hours
            );
        }
    }

    /**
     * Handles offensive casualties for the attacking dominion.
     *
     * Offensive casualties are 5% of the units sent.
     *
     * @param Dominion $dominion
     * @param array $units
     * @return array All the units that survived and will return home
     */
    protected function handleCasualties(Dominion $dominion, array $units): array
    {
        $offensiveCasualtiesPercentage = (static::CASUALTIES_BASE_PERCENTAGE / 100);

        $offensiveUnitsLost = [];

        foreach ($units as $slot => $amount) {
            $unitsToKill = (int)ceil($amount * $offensiveCasualtiesPercentage);
            $offensiveUnitsLost[$slot] = $unitsToKill;

            $fixedCasualtiesPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'fixed_casualties');
            if ($fixedCasualtiesPerk) {
                $fixedCasualtiesRatio = $fixedCasualtiesPerk / 100;
                $unitsActuallyKilled = (int)ceil($amount * $fixedCasualtiesRatio);
                $offensiveUnitsLost[$slot] = $unitsActuallyKilled;
            }
        }

        foreach ($offensiveUnitsLost as $slot => &$amount) {
            if ($amount > 0) {
                // Actually kill the units. RIP in peace, glorious warriors ;_;7
                $dominion->{"military_unit{$slot}"} -= $amount;

                $this->attackResult['attacker']['unitsLost'][$slot] = $amount;
            }
        }
        unset($amount); // Unset var by reference from foreach loop above to prevent unintended side-effects

        $survivingUnits = $units;

        foreach ($units as $slot => $amount) {
            if (isset($offensiveUnitsLost[$slot])) {
                $survivingUnits[$slot] -= $offensiveUnitsLost[$slot];
            }
        }

        return $survivingUnits;
    }

    /**
     * Handles the surviving units returning home.
     *
     * @param Dominion $dominion
     * @param array $units
     */
    protected function handleReturningUnits(Dominion $dominion, array $units): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $unitKey = "military_unit{$i}";
            $returningAmount = 0;

            if (array_key_exists($i, $units)) {
                $returningAmount += $units[$i];
                $dominion->$unitKey -= $units[$i];
            }

            if ($returningAmount === 0) {
                continue;
            }

            $this->queueService->queueResources(
                'invasion',
                $dominion,
                [$unitKey => $returningAmount],
                $this->invasionService->getUnitReturnHoursForSlot($dominion, $i)
            );
        }
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
