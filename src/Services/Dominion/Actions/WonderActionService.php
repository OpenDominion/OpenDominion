<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use LogicException;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\OpsCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\WonderCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Realm;
use OpenDominion\Models\RoundWonder;
use OpenDominion\Models\Spell;
use OpenDominion\Models\Wonder;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\InvasionService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Services\ValorService;
use OpenDominion\Traits\DominionGuardsTrait;
use OpenDominion\Traits\RealmGuardsTrait;

class WonderActionService
{
    use DominionGuardsTrait;
    use RealmGuardsTrait;

    /**
     * @var float Base percentage of offensive casualties
     */
    protected const CASUALTIES_BASE_PERCENTAGE = 3.5;

    /**
     * @var float Base percentage for cyclone damage cap
     */
    protected const CYCLONE_DAMAGE_CAP_PERCENTAGE = 0.75;

    /**
     * @var float Wizard multiplier for cyclone damage
     */
    protected const CYCLONE_DAMAGE_MULTIPLIER = 1.5;

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

    /** @var OpsCalculator */
    protected $opsCalculator;

    /** @var ProtectionService */
    protected $protectionService;

    /** @var QueueService */
    protected $queueService;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var SpellHelper */
    protected $spellHelper;

    /** @var ValorService */
    protected $valorService;

    /** @var WonderCalculator */
    protected $wonderCalculator;

    /** @var array Attack result array. todo: Should probably be refactored later to its own class */
    protected $attackResult = [
        'attacker' => [
            'unitsLost' => [],
            'unitsSent' => [],
            'damage' => 0,
            'op' => 0,
            'prestige' => 0,
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
     * @param OpsCalculator $opsCalculator
     * @param ProtectionService $protectionService
     * @param QueueService $queueService
     * @param SpellCalculator $spellCalculator
     * @param SpellHelper $spellHelper
     * @param ValorService $valorService
     * @param WonderCalculator $wonderCalculator
     */
    public function __construct(
        GovernmentService $governmentService,
        GuardMembershipService $guardMembershipService,
        InvasionService $invasionService,
        LandCalculator $landCalculator,
        MilitaryCalculator $militaryCalculator,
        NotificationService $notificationService,
        OpsCalculator $opsCalculator,
        ProtectionService $protectionService,
        QueueService $queueService,
        SpellCalculator $spellCalculator,
        SpellHelper $spellHelper,
        ValorService $valorService,
        WonderCalculator $wonderCalculator
    )
    {
        $this->governmentService = $governmentService;
        $this->guardMembershipService = $guardMembershipService;
        $this->invasionService = $invasionService;
        $this->landCalculator = $landCalculator;
        $this->militaryCalculator = $militaryCalculator;
        $this->notificationService = $notificationService;
        $this->opsCalculator = $opsCalculator;
        $this->protectionService = $protectionService;
        $this->queueService = $queueService;
        $this->spellCalculator = $spellCalculator;
        $this->spellHelper = $spellHelper;
        $this->valorService = $valorService;
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
        $this->guardGraveyardRealm($dominion->realm);
        $this->guardActionsDuringTick($dominion);

        $result = null;

        DB::transaction(function () use ($dominion, $wonder, &$result) {
            if ($dominion->wizard_strength < 30) {
                throw new GameException('Your wizards to not have enough strength to cast Cyclone');
            }

            $spell = Spell::where('key', 'cyclone')->first();
            $manaCost = $this->spellCalculator->getManaCost($dominion, $spell);

            if ($dominion->resource_mana < $manaCost) {
                throw new GameException('You do not have enough mana to cast Cyclone');
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

            if ($wonder->realm !== null && !$this->governmentService->canAttackWonders($dominion->realm, $wonder->realm)) {
                throw new GameException('You must be at war to cast spells at this wonder');
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
                throw new GameException("Your wizard force is too weak to cast {$spell->name}. Please train more wizards.");
            }

            $this->checkGuardApplications($dominion);

            $dominion->resource_mana -= $manaCost;
            $dominion->wizard_strength -= 5;
            $dominion->stat_spell_success += 1;

            $wizardRatio = min(1, $this->militaryCalculator->getWizardRatioRaw($dominion));
            $damageDealt = static::CYCLONE_DAMAGE_MULTIPLIER * $wizardRatio * $this->landCalculator->getTotalLand($dominion);
            $damageCap = static::CYCLONE_DAMAGE_CAP_PERCENTAGE / 100;

            // Techs
            $damageDealt *= (1 + $dominion->getTechPerkMultiplier('wonder_damage'));

            // Double damage if neutral wonder
            if ($this->attackResult['wonder']['neutral']) {
                $damageDealt *= 2;
            }

            // Cap at % of wonder max power
            $damageDealt = round(min($damageDealt, $wonder->power * $damageCap));
            $dominion->stat_cyclone_damage += $damageDealt;

            $wonderPower = max(0, $this->wonderCalculator->getCurrentPower($wonder) - $damageDealt);
            $wonder->damage()->create([
                'realm_id' => $dominion->realm_id,
                'dominion_id' => $dominion->id,
                'damage' => $damageDealt,
                'source' => 'cyclone'
            ]);

            $this->attackResult['attacker']['damage'] = $damageDealt;
            $this->attackResult['wonder']['power'] = $wonderPower;

            if ($wonderPower == 0) {
                $this->handleWonderDestroyed($wonder, $dominion, $currentRealm);
            }

            if ($this->attackResult['wonder']['destroyed']) {
                $result = [
                    'message' => sprintf(
                        'A twisting torrent of wind ravages the %s dealing %s damage and destroying it! You earned %s wizard mastery.',
                        $wonder->wonder->name,
                        $this->attackResult['attacker']['damage'],
                        $this->attackResult['attacker']['mastery']
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

            $dominion->save([
                'event' => HistoryService::EVENT_ACTION_CAST_SPELL,
                'action' => 'attack',
                'target_wonder_id' => $wonder->id
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
        $this->guardGraveyardRealm($dominion->realm);
        $this->guardActionsDuringTick($dominion);

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

            if ($wonder->realm !== null && !$this->governmentService->canAttackWonders($dominion->realm, $wonder->realm)) {
                throw new GameException('You must be at war to attack this wonder');
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

            if (!$this->invasionService->passes40PercentRule($dominion, null, $units)) {
                throw new GameException('You need to leave more DP units at home (40% rule)');
            }

            if (!$this->invasionService->passes54RatioRule($dominion, null, null, $units, true)) {
                throw new GameException('You are sending out too much OP, based on your new home DP (5:4 rule)');
            }

            foreach($units as $amount) {
                if($amount < 0) {
                    throw new GameException('Attack was canceled due to bad input');
                }
            }

            $this->checkGuardApplications($dominion);

            $damageDealt = round($this->militaryCalculator->getOffensivePowerRaw($dominion, null, null, $units));

            // Techs
            $damageDealt *= (1 + $dominion->getTechPerkMultiplier('wonder_damage'));

            $wonderPower = max(0, $this->wonderCalculator->getCurrentPower($wonder) - $damageDealt);
            $wonder->damage()->create([
                'realm_id' => $dominion->realm_id,
                'dominion_id' => $dominion->id,
                'damage' => $damageDealt,
                'source' => 'attack'
            ]);

            $this->attackResult['attacker']['op'] = $damageDealt;
            $this->attackResult['wonder']['power'] = $wonderPower;

            $this->handleBoats($dominion, $units);
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
                        ->sendNotifications($hostileDominion, 'irregular_realm');
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
                'Your army has attacked the %s dealing %s damage and destroying it! You earned %s prestige.',
                $wonder->wonder->name,
                $this->attackResult['attacker']['op'],
                $this->attackResult['attacker']['prestige']
            );
        } else {
            $message = sprintf(
                'Your army has attacked the %s dealing %s damage!',
                $wonder->wonder->name,
                $this->attackResult['attacker']['op']
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

        $friendlyDominions = $dominion->realm->dominions;
        $detroyedByRealm = $dominion->realm;
        $dominion->stat_wonders_destroyed += 1;

        if ($dominion->realm->wonders->isEmpty()) {
            $wonder->realm_id = $dominion->realm_id;
            $wonder->power = $this->wonderCalculator->getNewPower($wonder, $detroyedByRealm);
        } else {
            $wonder->realm_id = null;
            $wonder->power = $this->wonderCalculator->getNewPower($wonder, $detroyedByRealm);
        }

        $prestigeRewards = [];
        $masteryRewards = [];
        foreach ($friendlyDominions as $friendlyDominion) {
            // Rewards
            $prestigeGain = $this->wonderCalculator->getPrestigeGainForDominion($wonder, $friendlyDominion);
            $masteryGain = $this->wonderCalculator->getMasteryGainForDominion($wonder, $friendlyDominion);
            if ($friendlyDominion->id == $dominion->id) {
                $dominion->prestige += $prestigeGain;
                $dominion->wizard_mastery += $masteryGain;
                $this->attackResult['attacker']['prestige'] = $prestigeGain;
                $this->attackResult['attacker']['mastery'] = $masteryGain;
            } else {
                $friendlyDominion->prestige += $prestigeGain;
                $friendlyDominion->wizard_mastery += $masteryGain;
                $friendlyDominion->save(['event' => HistoryService::EVENT_ACTION_WONDER_DESTROYED]);
            }
            $prestigeRewards[$friendlyDominion->id] = $prestigeGain;
            $masteryRewards[$friendlyDominion->id] = $masteryGain;

            // Valor
            $damageContribution = $this->wonderCalculator->getDamageContribution($wonder, $friendlyDominion);
            if ($currentRealm !== null) {
                $valorService->awardValor($friendlyDominion, 'wonder', $damageContribution);
            } else {
                $valorService->awardValor($friendlyDominion, 'wonder_neutral', $damageContribution);
            }
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

        // Queue friendly notifications
        foreach ($friendlyDominions as $friendlyDominion) {
            $this->notificationService
                ->queueNotification('wonder_rebuilt', [
                    'prestige' => isset($prestigeRewards[$friendlyDominion->id]) ? $prestigeRewards[$friendlyDominion->id] : 0,
                    'mastery' => isset($masteryRewards[$friendlyDominion->id]) ? $masteryRewards[$friendlyDominion->id] : 0,
                    'wonderRealmId' => $wonder->realm_id,
                    'wonderId' => $wonder->wonder->id
                ])
                ->sendNotifications($friendlyDominion, 'irregular_realm');
        }

        if ($currentRealm !== null) {
            // Queue hostile notifications
            foreach ($currentRealm->dominions as $hostileDominion) {
                $this->notificationService
                    ->queueNotification('wonder_destroyed', [
                        'attackerRealmId' => $dominion->realm->id,
                        'wonderId' => $wonder->wonder->id
                    ])
                    ->sendNotifications($hostileDominion, 'irregular_realm');
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
            $boatsByReturnHourGroup = (int)floor($amountUnits / $this->militaryCalculator->getBoatCapacity($dominion));

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
        $offensiveCasualtiesPercentage = static::CASUALTIES_BASE_PERCENTAGE / 100;
        $offensiveCasualtiesPercentage *= (1 + $dominion->getTechPerkMultiplier('casualties_wonders'));

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
