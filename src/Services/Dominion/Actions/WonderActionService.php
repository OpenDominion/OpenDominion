<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use LogicException;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\WonderCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\OpsHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Realm;
use OpenDominion\Models\RoundWonder;
use OpenDominion\Models\RoundWonderDamage;
use OpenDominion\Models\Wonder;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\InvasionService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Traits\DominionGuardsTrait;

class WonderActionService
{
    use DominionGuardsTrait;

    /**
     * @var float Base percentage of offensive casualties
     */
    protected const CASUALTIES_BASE_PERCENTAGE = 5;

    /**
     * @var float Base percentage for wizards killed from spell failure
     */
    protected const CYCLONE_WIZARD_LOSSES_PERCENTAGE = 0.25;

    /**
     * @var float Wonder defensive WPA when calculating success rates
     */
    protected const WONDER_WPA = 0.25;

    /** @var GovernmentService */
    protected $governmentService;

    /** @var GuardMembershipService */
    protected $guardMembershipService;

    /** @var InvasionService */
    protected $invasionService;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var NotificationService */
    protected $notificationService;

    /** @var OpsHelper */
    protected $opsHelper;

    /** @var ProtectionService */
    protected $protectionService;

    /** @var QueueService */
    protected $queueService;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var SpellHelper */
    protected $spellHelper;

    /** @var WonderCalculator */
    protected $wonderCalculator;

    /** @var array Attack result array. todo: Should probably be refactored later to its own class */
    protected $attackResult = [
        'attacker' => [
            'unitsLost' => [],
            'unitsSent' => [],
        ],
        'wonder' => [
            'currentRealmId' => null,
            'destroyedByRealmId' => null,
            'destroyed' => false,
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
     * @param GovernmentService $governmentService
     * @param GuardMembershipService $guardMembershipService
     * @param InvasionService $invasionService
     * @param LandCalculator $landCalculator
     * @param MilitaryCalculator $militaryCalculator
     * @param NotificationService $notificationService
     * @param OpsHelper $opsHelper
     * @param ProtectionService $protectionService
     * @param QueueService $queueService
     * @param SpellCalculator $spellCalculator
     * @param SpellHelper $spellHelper
     * @param WonderCalculator $wonderCalculator
     */
    public function __construct(
        GovernmentService $governmentService,
        GuardMembershipService $guardMembershipService,
        InvasionService $invasionService,
        LandCalculator $landCalculator,
        MilitaryCalculator $militaryCalculator,
        NotificationService $notificationService,
        OpsHelper $opsHelper,
        ProtectionService $protectionService,
        QueueService $queueService,
        SpellCalculator $spellCalculator,
        SpellHelper $spellHelper,
        WonderCalculator $wonderCalculator
    ) {
        $this->governmentService = $governmentService;
        $this->guardMembershipService = $guardMembershipService;
        $this->invasionService = $invasionService;
        $this->landCalculator = $landCalculator;
        $this->militaryCalculator = $militaryCalculator;
        $this->notificationService = $notificationService;
        $this->opsHelper = $opsHelper;
        $this->protectionService = $protectionService;
        $this->queueService = $queueService;
        $this->spellCalculator = $spellCalculator;
        $this->spellHelper = $spellHelper;
        $this->wonderCalculator = $wonderCalculator;
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
    public function castCyclone(Dominion $dominion, RoundWonder $wonder): array
    {
        $this->guardLockedDominion($dominion);

        $result = null;

        DB::transaction(function () use ($dominion, $wonder, &$result) {
            if ($dominion->wizard_strength < 30) {
                throw new GameException("Your wizards to not have enough strength to cast Lightning Storm");
            }
    
            $spellInfo = $this->spellHelper->getSpellInfo('cyclone');
            $manaCost = $this->spellCalculator->getManaCost($dominion, 'cyclone');
    
            if ($dominion->resource_mana < $manaCost) {
                throw new GameException("You do not have enough mana to cast Lightning Storm.");
            }
    
            if ($this->protectionService->isUnderProtection($dominion)) {
                throw new GameException('You cannot cast offensive spells while under protection');
            }
    
            if ($dominion->round->hasOffensiveActionsDisabled()) {
                throw new GameException('Black ops have been disabled for the remainder of the round');
            }

            if ($dominion->round->id !== $wonder->round->id) {
                throw new GameException('Nice try, but you cannot cast spells cross-round');
            }

            if ($wonder->realm !== null && !$this->governmentService->isAtWarWithRealm($dominion->realm, $wonder->realm)) {
                throw new GameException('War must be active to cast spells at this wonder');
            }

            if ($this->guardMembershipService->isGuardMember($dominion)) {
                throw new GameException('You must leave the Royal Guard to cast spells at this wonder');
            }

            $currentRealm = $wonder->realm;
            $this->attackResult['wonder']['currentRealmId'] = $wonder->realm_id;
            $this->attackResult['wonder']['neutral'] = ($wonder->realm_id == null);

            // TODO: Refactor all spy/wizard losses
            $selfWpa = $this->militaryCalculator->getWizardRatio($dominion, 'offense');

            // You need at least some positive WPA to cast black ops
            if ($selfWpa === 0.0) {
                // Don't reduce mana by throwing an exception here
                throw new GameException("Your wizard force is too weak to cast {$spellInfo['name']}. Please train more wizards.");
            }

            $this->checkGuardApplications($dominion);

            $dominion->resource_mana -= $manaCost;
            $dominion->wizard_strength -= min($dominion->wizard_strength, 5);

            $successRate = $this->opsHelper->blackOperationSuccessChance($selfWpa, static::WONDER_WPA);
            if ($wonder->wonder->perks->pluck('key')->contains('enemy_spell_chance')) {
                $successRate *= (1 - $wonder->wonder->perks->groupBy('key')['enemy_spell_chance']->first()->pivot->value / 100);
            }

            if (!random_chance($successRate)) {
                $dominion->stat_spell_failure += 1;

                $wizardsKilledPercentage = static::CYCLONE_WIZARD_LOSSES_PERCENTAGE / 100;

                $unitsKilled = [];
                $wizardsKilled = (int)floor($dominion->military_wizards * $wizardsKilledPercentage);

                // Check for immortal wizards
                if ($dominion->race->getPerkValue('immortal_wizards') != 0) {
                    $wizardsKilled = 0;
                }

                if ($wizardsKilled > 0) {
                    $unitsKilled['wizards'] = $wizardsKilled;
                    $dominion->military_wizards -= $wizardsKilled;
                }

                foreach ($dominion->race->units as $unit) {
                    if ($unit->getPerkValue('counts_as_wizard_offense')) {
                        $unitKilledMultiplier = ((float)$unit->getPerkValue('counts_as_wizard_offense') / 2) * $wizardsKilledPercentage;
                        $unitKilled = (int)floor($dominion->{"military_unit{$unit->slot}"} * $unitKilledMultiplier);
                        if ($unitKilled > 0) {
                            $unitsKilled[strtolower($unit->name)] = $unitKilled;
                            $dominion->{"military_unit{$unit->slot}"} -= $unitKilled;
                        }
                    }
                }

                $dominion->stat_wizards_lost += array_sum($unitsKilled);

                $unitsKilledStringParts = [];
                foreach ($unitsKilled as $name => $amount) {
                    $amountLabel = number_format($amount);
                    $unitLabel = str_plural(str_singular($name), $amount);
                    $unitsKilledStringParts[] = "{$amountLabel} {$unitLabel}";
                }
                $unitsKilledString = generate_sentence_from_array($unitsKilledStringParts);

                if ($unitsKilledString) {
                    $message = "The wonder has repelled our {$spellInfo['name']} attempt and managed to kill $unitsKilledString.";
                } else {
                    $message = "The wonder has repelled our {$spellInfo['name']} attempt.";
                }

                $result = [
                    'message' => $message,
                    'alert-type' => 'warning'
                ];
            } else {
                $dominion->stat_spell_success += 1;

                $wizardRatio = min(1, $this->militaryCalculator->getWizardRatioRaw($dominion));
                $damageDealt = round($spellInfo['damage_multiplier'] * $wizardRatio * $this->landCalculator->getTotalLand($dominion));
                if ($wonder->wonder->perks->pluck('key')->contains('enemy_spell_damage')) {
                    $damageDealt *= (1 + $wonder->wonder->perks->groupBy('key')['enemy_spell_damage']->first()->pivot->value / 100);
                }
                $dominion->stat_cyclone_damage += $damageDealt;

                $wonderPower = max(0, $this->wonderCalculator->getCurrentPower($wonder) - $damageDealt);
                $wonder->damage()->create([
                    'realm_id' => $dominion->realm_id,
                    'dominion_id' => $dominion->id,
                    'damage' => $damageDealt
                ]);

                $this->attackResult['attacker']['damage'] = $damageDealt;
                $this->attackResult['wonder']['power'] = $wonderPower;

                if ($wonderPower == 0) {
                    $this->handleWonderDestroyed($wonder, $dominion, $currentRealm);
                }

                if ($this->attackResult['wonder']['destroyed']) {
                    $result = [
                        'message' => sprintf(
                            'A twisting torrent of wind ravages the %s dealing %s damage, and destroying it!',
                            $wonder->wonder->name,
                            $this->attackResult['attacker']['damage']
                        ),
                        'alert-type' => 'success'
                    ];
                } else {
                    $result = [
                        'message' => sprintf(
                            'A twisting torrent of wind ravages the %s dealing %s damage!',
                            $wonder->wonder->name,
                            $this->attackResult['attacker']['damage']
                        ),
                        'alert-type' => 'success'
                    ];
                }
            }

            // TODO: Add target wonder id?
            $dominion->save([
                'event' => HistoryService::EVENT_ACTION_CAST_SPELL,
                'action' => 'cyclone'
            ]);
            $wonder->save();
        });

        return $result;
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
                throw new GameException('Attacks have been disabled for the remainder of the round');
            }

            if ($this->protectionService->isUnderProtection($dominion)) {
                throw new GameException('You cannot attack while under protection');
            }

            if ($dominion->round->id !== $wonder->round->id) {
                throw new GameException('Nice try, but you cannot attack cross-round');
            }

            if ($wonder->realm !== null && !$this->governmentService->isAtWarWithRealm($dominion->realm, $wonder->realm)) {
                throw new GameException('War must be active to attack this wonder');
            }

            if ($this->guardMembershipService->isGuardMember($dominion)) {
                throw new GameException('You must leave the Royal Guard to attack this wonder');
            }

            $currentRealm = $wonder->realm;
            $this->attackResult['wonder']['currentRealmId'] = $wonder->realm_id;
            $this->attackResult['wonder']['neutral'] = ($wonder->realm_id == null);

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

            if (!$this->invasionService->passes33PercentRule($dominion, null, $units)) {
                throw new GameException('You need to leave more DP units at home (33% rule)');
            }

            if (!$this->invasionService->passes54RatioRule($dominion, null, null, $units)) {
                throw new GameException('You are sending out too much OP, based on your new home DP (5:4 rule)');
            }

            foreach($units as $amount) {
                if($amount < 0) {
                    throw new GameException('Attack was canceled due to bad input');
                }
            }

            $this->checkGuardApplications($dominion);

            $damageDealt = round($this->militaryCalculator->getOffensivePowerRaw($dominion, null, null, $units));
            $wonderPower = max(0, $this->wonderCalculator->getCurrentPower($wonder) - $damageDealt);
            $wonder->damage()->create([
                'realm_id' => $dominion->realm_id,
                'dominion_id' => $dominion->id,
                'damage' => $damageDealt
            ]);

            $this->attackResult['attacker']['op'] = $damageDealt;
            $this->attackResult['wonder']['power'] = $wonderPower;

            $this->handleBoats($dominion, $units);
            $this->handleResearchPoints($dominion, $units);
            $survivingUnits = $this->handleCasualties($dominion, $units);
            $this->handleReturningUnits($dominion, $survivingUnits);

            $this->attackResult['attacker']['unitsSent'] = $units;

            $dominion->morale -= 5;
            $dominion->stat_wonder_damage += $damageDealt;

            $this->attackEvent = GameEvent::create([
                'round_id' => $dominion->round->id,
                'source_type' => Dominion::class,
                'source_id' => $dominion->id,
                'target_type' => RoundWonder::class,
                'target_id' => $wonder->id,
                'type' => 'wonder_attacked',
                'data' => $this->attackResult
            ]);

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

            if ($wonderPower == 0) {
                $this->handleWonderDestroyed($wonder, $dominion, $currentRealm);
            }

            // TODO: Add target wonder id?
            $dominion->save(['event' => HistoryService::EVENT_ACTION_WONDER_ATTACKED]);
            $wonder->save();
        });

        if ($this->attackResult['wonder']['destroyed']) {
            $message = sprintf(
                'Your army has attacked the %s dealing %s damage, destroying it!',
                $this->attackResult['attacker']['op'],
                $wonder->wonder->name
            );
        } else {
            $message = sprintf(
                'Your army has attacked the %s dealing %s damage!',
                $this->attackResult['attacker']['op'],
                $wonder->wonder->name
            );
        }

        return [
            'message' => $message,
            'alert-type' => 'success',
            'redirect' => route('dominion.event', [$this->attackEvent->id])
        ];
    }

    /**
     * Handles the destruction of a wonder.
     *
     * @param RoundWonder $wonder
     * @param Dominion $dominion
     * @param Realm $currentRealm
     */
    protected function handleWonderDestroyed(RoundWonder $wonder, Dominion $dominion, ?Realm $currentRealm): void
    {
        $this->attackResult['wonder']['destroyedByRealmId'] = $dominion->realm->id;
        $this->attackResult['wonder']['destroyed'] = true;

        $detroyedByRealm = $dominion->realm;
        $dominion->stat_wonders_destroyed += 1;

        if ($wonder->realm !== null) {
            foreach ($dominion->realm->dominions as $friendlyDominion) {
                $prestigeGain = $this->wonderCalculator->getPrestigeGainForDominion($wonder, $friendlyDominion);
                if ($friendlyDominion->id == $dominion->id) {
                    $dominion->prestige += $prestigeGain;
                } else {
                    $friendlyDominion->prestige += $prestigeGain;
                    $friendlyDominion->save(['event' => HistoryService::EVENT_ACTION_WONDER_DESTROYED]);
                }
            }
        }

        if ($dominion->realm->wonders->isEmpty()) {
            $victorRealm = $dominion->realm;
            $wonder->realm_id = $victorRealm->id;
            $wonder->power = $this->wonderCalculator->getNewPower($wonder, $detroyedByRealm);
        } else {
            $victorRealm = null;
            $wonder->realm_id = null;
            $wonder->power = $this->wonderCalculator->getNewPower($wonder, $detroyedByRealm);
        }

        $wonder->damage()->delete();

        $this->attackResult['wonder']['power'] = $wonder->power;
        $this->attackResult['wonder']['victorRealmId'] = $wonder->realm_id;

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

    /**
     * Resets guard application status of $dominion.
     *
     * @param Dominion $dominion
     */
    public function checkGuardApplications(Dominion $dominion): void
    {
        if ($this->guardMembershipService->isRoyalGuardApplicant($dominion)) {
            $this->guardMembershipService->joinRoyalGuard($dominion);
        }
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
     * Handles research point generation for attacker.
     *
     * @param RoundWonder $wonder
     * @param Dominion $dominion
     * @param array $units
     */
    protected function handleResearchPoints(RoundWonder $wonder, Dominion $dominion, array $units): void
    {
        $mindSwellActive = $sc->getActiveSpells($dominion, true)->firstWhere('spell', 'mindswell');
        if ($mindSwellActive !== null) {
            $offenseSent = $this->militaryCalculator->getOffensivePowerRaw($dominion, null, null, $units);
            $researchPointsGained = $this->WonderCalculator->getTechGainForDominion($wonder, $dominion, $offenseSent);

            if ($researchPointsGained > 0) {
                $slowestTroopsReturnHours = $this->invasionService->getSlowestUnitReturnHours($dominion, $units);

                $this->queueService->queueResources(
                    'invasion',
                    $dominion,
                    ['resource_tech' => $researchPointsGained],
                    $slowestTroopsReturnHours
                );

                $this->attackResult['attacker']['researchPoints'] = $researchPointsGained;

                // Remove spell after use
                DB::table('active_spells')
                    ->where([
                        'dominion_id' => $dominion->id,
                        'spell' => $spellKey,
                    ])
                    ->delete();
            }
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
}
