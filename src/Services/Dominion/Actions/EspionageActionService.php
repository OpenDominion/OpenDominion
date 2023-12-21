<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use Exception;
use LogicException;
use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\OpsCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\EspionageHelper;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Mappers\Dominion\InfoMapper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\InfoOp;
use OpenDominion\Services\Dominion\BountyService;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Traits\DominionGuardsTrait;

class EspionageActionService
{
    use DominionGuardsTrait;

    /** @var BountyService */
    protected $bountyService;

    /** @var BuildingHelper */
    protected $buildingHelper;

    /** @var EspionageHelper */
    protected $espionageHelper;

    /** @var GovernmentService */
    protected $governmentService;

    /** @var GuardMembershipService */
    protected $guardMembershipService;

    /** @var HeroCalculator */
    protected $heroCalculator;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var ImprovementHelper */
    protected $improvementHelper;

    /** @var InfoMapper */
    protected $infoMapper;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var LandHelper */
    protected $landHelper;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var NotificationService */
    protected $notificationService;

    /** @var OpsCalculator */
    protected $opsCalculator;

    /** @var ProductionCalculator */
    protected $productionCalculator;

    /** @var ProtectionService */
    protected $protectionService;

    /** @var QueueService */
    protected $queueService;

    /** @var RangeCalculator */
    protected $rangeCalculator;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /**
     * EspionageActionService constructor.
     */
    public function __construct()
    {
        $this->bountyService = app(BountyService::class);
        $this->buildingHelper = app(BuildingHelper::class);
        $this->espionageHelper = app(EspionageHelper::class);
        $this->governmentService = app(GovernmentService::class);
        $this->guardMembershipService = app(GuardMembershipService::class);
        $this->heroCalculator = app(HeroCalculator::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->improvementHelper = app(ImprovementHelper::class);
        $this->infoMapper = app(InfoMapper::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->landHelper = app(LandHelper::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->notificationService = app(NotificationService::class);
        $this->opsCalculator = app(OpsCalculator::class);
        $this->productionCalculator = app(ProductionCalculator::class);
        $this->protectionService = app(ProtectionService::class);
        $this->queueService = app(QueueService::class);
        $this->rangeCalculator = app(RangeCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);
    }

    public const BLACK_OPS_HOURS_AFTER_ROUND_START = 24 * 3;
    public const THEFT_HOURS_AFTER_ROUND_START = 24 * 3;

    /**
     * Performs a espionage operation for $dominion, aimed at $target dominion.
     *
     * @param Dominion $dominion
     * @param string $operationKey
     * @param Dominion $target
     * @return array
     * @throws GameException
     * @throws LogicException
     */
    public function performOperation(Dominion $dominion, string $operationKey, Dominion $target): array
    {
        $this->guardLockedDominion($dominion);
        $this->guardLockedDominion($target);
        $this->guardActionsDuringTick($dominion);

        $operationInfo = $this->espionageHelper->getOperationInfo($operationKey);

        if (!$operationInfo) {
            throw new LogicException("Cannot perform unknown operation '{$operationKey}'");
        }

        if ($dominion->spy_strength < 30) {
            throw new GameException("Your spies do not have enough strength to perform {$operationInfo['name']}.");
        }

        if ($this->protectionService->isUnderProtection($dominion)) {
            throw new GameException('You cannot perform espionage operations while under protection');
        }

        if ($this->protectionService->isUnderProtection($target)) {
            throw new GameException('You cannot perform espionage operations on targets which are under protection');
        }

        if (!$this->rangeCalculator->isInRange($dominion, $target) && !in_array($target->id, $this->militaryCalculator->getRecentlyInvadedBy($dominion, 12))) {
            throw new GameException('You cannot perform espionage operations on targets outside of your range');
        }

        if ($this->espionageHelper->isResourceTheftOperation($operationKey)) {
            if (now()->diffInHours($dominion->round->start_date) < self::THEFT_HOURS_AFTER_ROUND_START) {
                throw new GameException('You cannot perform resource theft for the first three days of the round');
            }
            if ($this->rangeCalculator->getDominionRange($dominion, $target) < 100) {
                throw new GameException('You cannot perform resource theft on targets smaller than yourself');
            }
            if ($target->user_id == null) {
                throw new GameException('You cannot perform resource theft on bots');
            }
        } elseif ($this->espionageHelper->isHostileOperation($operationKey)) {
            if (now()->diffInHours($dominion->round->start_date) < self::BLACK_OPS_HOURS_AFTER_ROUND_START) {
                throw new GameException('You cannot perform black ops for the first three days of the round');
            }
            if ($target->user_id == null) {
                throw new GameException('You cannot perform black ops on bots');
            }
        }

        if ($dominion->round->id !== $target->round->id) {
            throw new GameException('Nice try, but you cannot perform espionage operations cross-round');
        }

        if ($dominion->realm->id === $target->realm->id) {
            throw new GameException('Nice try, but you cannot perform espionage oprations on your realmies');
        }

        $result = null;
        $bountyMessage = '';
        $xpMessage = '';

        DB::transaction(function () use ($dominion, $target, $operationKey, &$result, &$bountyMessage, &$xpMessage) {
            $xpGain = 0;

            if ($this->espionageHelper->isInfoGatheringOperation($operationKey)) {
                $xpGain = 1;
                $spyStrengthLost = 2;
                if ($this->guardMembershipService->isBlackGuardMember($dominion)) {
                    $spyStrengthLost = 1;
                }
                $result = $this->performInfoGatheringOperation($dominion, $operationKey, $target);
            } elseif ($this->espionageHelper->isResourceTheftOperation($operationKey)) {
                $spyStrengthLost = 5;
                $result = $this->performResourceTheftOperation($dominion, $operationKey, $target);
            } elseif ($this->espionageHelper->isHostileOperation($operationKey)) {
                if ($this->espionageHelper->isWarOperation($operationKey)) {
                    $xpGain = 6;
                } else {
                    $xpGain = 4;
                }
                $spyStrengthLost = 5;
                $result = $this->performHostileOperation($dominion, $operationKey, $target);
                if (isset($result['damage']) && $result['damage'] == 0) {
                    $xpGain = 0;
                }
                $dominion->resetAbandonment();
            } else {
                throw new LogicException("Unknown type for espionage operation {$operationKey}");
            }

            // No XP for bots
            if ($target && $target->user_id == null) {
                $xpGain = 0;
            }

            $dominion->spy_strength -= $spyStrengthLost;

            if ($result['success']) {
                $dominion->stat_espionage_success += 1;
                // Bounty result
                if (isset($result['bounty']) && $result['bounty']) {
                    $bountyRewardString = '';
                    $rewards = $result['bounty'];
                    if (isset($rewards['xp'])) {
                        $xpGain += $rewards['xp'];
                    }
                    if (isset($rewards['resource']) && isset($rewards['amount'])) {
                        $dominion->{$rewards['resource']} += $rewards['amount'];
                        $bountyRewardString = sprintf(' awarding %d %s', $rewards['amount'], dominion_attr_display($rewards['resource'], $rewards['amount']));
                    }
                    $bountyMessage = sprintf('You collected a bounty%s.', $bountyRewardString);
                }
                // Hero Experience
                if ($dominion->hero && $xpGain) {
                    $xpGain = $this->heroCalculator->getExperienceGain($dominion, $xpGain);
                    $dominion->hero->experience += $xpGain;
                    $dominion->hero->save();
                    $xpMessage = sprintf(' You gain %.3g XP.', $xpGain);
                }
            } else {
                $dominion->stat_espionage_failure += 1;
            }

            $dominion->save([
                'event' => HistoryService::EVENT_ACTION_PERFORM_ESPIONAGE_OPERATION,
                'action' => $operationKey,
                'target_dominion_id' => $target->id
            ]);

            if ($dominion->fresh()->spy_strength < 25) {
                throw new GameException('Your spies have run out of strength');
            }

            $target->save([
                'event' => HistoryService::EVENT_ACTION_RECEIVE_ESPIONAGE_OPERATION,
                'action' => $operationKey,
                'source_dominion_id' => $dominion->id
            ]);
        });

        $this->rangeCalculator->checkGuardApplications($dominion, $target);

        return [
                'message' => sprintf('%s %s %s', $result['message'], $bountyMessage, $xpMessage),
                'data' => [
                    'operation' => $operationKey,
                ],
                'redirect' =>
                    $this->espionageHelper->isInfoGatheringOperation($operationKey) && $result['success']
                        ? route('dominion.op-center.show', $target->id)
                        : null,
            ] + $result;
    }

    /**
     * @param Dominion $dominion
     * @param string $operationKey
     * @param Dominion $target
     * @return array
     * @throws Exception
     */
    protected function performInfoGatheringOperation(Dominion $dominion, string $operationKey, Dominion $target): array
    {
        $operationInfo = $this->espionageHelper->getOperationInfo($operationKey);

        $selfSpa = $this->militaryCalculator->getSpyRatio($dominion, 'offense');
        $targetSpa = $this->militaryCalculator->getSpyRatio($target, 'defense');

        // You need at least some positive SPA to perform espionage operations
        if ($selfSpa == 0) {
            // Don't reduce spy strength by throwing an exception here
            throw new GameException("Your spy force is too weak to perform {$operationInfo['name']}. Please train some more spies.");
        }

        $successRate = $this->opsCalculator->infoOperationSuccessChance($selfSpa, $targetSpa, $dominion->spy_strength, $target->spy_strength);

        // Wonders
        $successRate *= (1 - $target->getWonderPerkMultiplier('enemy_espionage_chance'));

        if (!random_chance($successRate)) {
            list($unitsKilled, $unitsKilledString) = $this->handleLosses($dominion, $target, 'info');

            // Inform target that they repelled a hostile spy operation
            $this->notificationService
                ->queueNotification('repelled_spy_op', [
                    'sourceDominionId' => $dominion->id,
                    'operationKey' => $operationKey,
                    'unitsKilled' => $unitsKilledString,
                ])
                ->sendNotifications($target, 'irregular_dominion');

            if ($unitsKilledString) {
                $message = sprintf(
                    'The enemy has prevented our %s attempt and managed to capture %s.',
                    $operationInfo['name'],
                    $unitsKilledString
                );
            } else {
                $message = sprintf(
                    'The enemy has prevented our %s attempt.',
                    $operationInfo['name']
                );
            }

            return [
                'success' => false,
                'message' => $message,
                'alert-type' => 'warning',
            ];
        }

        $infoOp = new InfoOp([
            'source_realm_id' => $dominion->realm->id,
            'target_realm_id' => $target->realm->id,
            'type' => $operationKey,
            'source_dominion_id' => $dominion->id,
            'target_dominion_id' => $target->id,
        ]);

        switch ($operationKey) {
            case 'barracks_spy':
                $infoOp->data = $this->infoMapper->mapMilitary($target);
                break;

            case 'castle_spy':
                $infoOp->data = $this->infoMapper->mapImprovements($target);
                break;

            case 'survey_dominion':
                $infoOp->data = $this->infoMapper->mapBuildings($target);
                break;

            case 'land_spy':
                $infoOp->data = $this->infoMapper->mapLand($target);
                break;

            default:
                throw new LogicException("Unknown info gathering operation {$operationKey}");
        }

        // Surreal Perception
        if ($target->getSpellPerkValue('surreal_perception') || $target->getWonderPerkValue('surreal_perception')) {
            $this->notificationService
                ->queueNotification('received_spy_op', [
                    'sourceDominionId' => $dominion->id,
                    'operationKey' => $operationKey,
                ])
                ->sendNotifications($target, 'irregular_dominion');
        }

        $infoOp->save();

        $bountyRewards = $this->bountyService->collectBounty($dominion, $target, $operationKey);

        return [
            'success' => true,
            'message' => 'Your spies infiltrate the target\'s dominion successfully and return with a wealth of information.',
            'bounty' => $bountyRewards
        ];
    }

    /**
     * @param Dominion $dominion
     * @param string $operationKey
     * @param Dominion $target
     * @return array
     * @throws Exception
     */
    protected function performResourceTheftOperation(Dominion $dominion, string $operationKey, Dominion $target): array
    {
        if ($dominion->round->hasOffensiveActionsDisabled())
        {
            throw new GameException('Theft has been disabled for the remainder of the round.');
        }

        $operationInfo = $this->espionageHelper->getOperationInfo($operationKey);

        $selfSpa = $this->militaryCalculator->getSpyRatio($dominion, 'offense');
        $targetSpa = $this->militaryCalculator->getSpyRatio($target, 'defense');

        // You need at least some positive SPA to perform espionage operations
        if ($selfSpa == 0) {
            // Don't reduce spy strength by throwing an exception here
            throw new GameException("Your spy force is too weak to perform {$operationInfo['name']}. Please train some more spies.");
        }

        $successRate = $this->opsCalculator->theftOperationSuccessChance($selfSpa, $targetSpa, $dominion->spy_strength, $target->spy_strength);

        // Wonders
        $successRate *= (1 - $target->getWonderPerkMultiplier('enemy_espionage_chance'));

        if (!random_chance($successRate)) {
            list($unitsKilled, $unitsKilledString) = $this->handleLosses($dominion, $target, 'theft');

            $this->notificationService
                ->queueNotification('repelled_resource_theft', [
                    'sourceDominionId' => $dominion->id,
                    'operationKey' => $operationKey,
                    'unitsKilled' => $unitsKilledString,
                ])
                ->sendNotifications($target, 'irregular_dominion');

            if ($unitsKilledString) {
                $message = "The enemy has prevented our {$operationInfo['name']} attempt and managed to capture $unitsKilledString.";
            } else {
                $message = "The enemy has prevented our {$operationInfo['name']} attempt.";
            }

            return [
                'success' => false,
                'message' => $message,
                'alert-type' => 'warning',
            ];
        }

        switch ($operationKey) {
            case 'steal_platinum':
                $resource = 'platinum';
                $constraints = [
                    'target_amount' => 2,
                    'self_production' => 75,
                    'spy_carries' => 45,
                ];
                break;

            case 'steal_food':
                $resource = 'food';
                $constraints = [
                    'target_amount' => 2,
                    'self_production' => 100,
                    'spy_carries' => 50,
                ];
                break;

            case 'steal_lumber':
                $resource = 'lumber';
                $constraints = [
                    'target_amount' => 5,
                    'self_production' => 75,
                    'spy_carries' => 50,
                ];
                break;

            case 'steal_mana':
                $resource = 'mana';
                $constraints = [
                    'target_amount' => 3,
                    'self_production' => 56,
                    'spy_carries' => 50,
                ];
                break;

            case 'steal_ore':
                $resource = 'ore';
                $constraints = [
                    'target_amount' => 5,
                    'self_production' => 68,
                    'spy_carries' => 50,
                ];
                break;

            case 'steal_gems':
                $resource = 'gems';
                $constraints = [
                    'target_amount' => 2,
                    'self_production' => 100,
                    'spy_carries' => 50,
                ];
                break;

            default:
                throw new LogicException("Unknown resource theft operation {$operationKey}");
        }

        $amountStolen = $this->getResourceTheftAmount($dominion, $target, $resource, $constraints);

        $dominion->{"resource_{$resource}"} += $amountStolen;
        $dominion->{"stat_total_{$resource}_stolen"} += $amountStolen;
        $target->{"resource_{$resource}"} -= $amountStolen;

        // Surreal Perception
        $sourceDominionId = null;
        if ($target->getSpellPerkValue('surreal_perception') || $target->getWonderPerkValue('surreal_perception')) {
            $sourceDominionId = $dominion->id;
        }

        $this->notificationService
            ->queueNotification('resource_theft', [
                'sourceDominionId' => $sourceDominionId,
                'operationKey' => $operationKey,
                'amount' => $amountStolen,
                'resource' => $resource,
            ])
            ->sendNotifications($target, 'irregular_dominion');

        return [
            'success' => true,
            'message' => sprintf(
                'Your spies infiltrate the target\'s dominion successfully and return with %s %s.',
                number_format($amountStolen),
                $resource
            ),
        ];
    }

    protected function getResourceTheftAmount(
        Dominion $dominion,
        Dominion $target,
        string $resource,
        array $constraints
    ): int {
        if ($target->getSpellPerkValue('fools_gold')) {
            if ($resource === 'platinum') {
                return 0;
            }
            if ($target->getTechPerkValue('improved_fools_gold') != 0 && ($resource === 'ore' || $resource === 'lumber' || $resource === 'mana')) {
                return 0;
            }
        }
        // Limit to percentage of target's raw production
        $maxTarget = true;
        if ($constraints['target_amount'] > 0) {
            $maxTarget = $target->{'resource_' . $resource} * $constraints['target_amount'] / 100;
        }

        // Limit to percentage of dominion's raw production
        $maxDominion = true;
        if ($constraints['self_production'] > 0) {
            if ($resource === 'platinum') {
                $maxDominion = floor($this->productionCalculator->getPlatinumProductionRaw($dominion) * $constraints['self_production'] / 100);
            } elseif ($resource === 'food') {
                $maxDominion = floor($this->productionCalculator->getFoodProductionRaw($dominion) * $constraints['self_production'] / 100);
            } elseif ($resource === 'lumber') {
                $maxDominion = floor($this->productionCalculator->getLumberProductionRaw($dominion) * $constraints['self_production'] / 100);
            } elseif ($resource === 'mana') {
                $maxDominion = floor($this->productionCalculator->getManaProductionRaw($dominion) * $constraints['self_production'] / 100);
            } elseif ($resource === 'ore') {
                $maxDominion = floor($this->productionCalculator->getOreProductionRaw($dominion) * $constraints['self_production'] / 100);
            } elseif ($resource === 'gems') {
                $maxDominion = floor($this->productionCalculator->getGemProductionRaw($dominion) * $constraints['self_production'] / 100);
            }
        }

        // Limit to amount carryable by spies
        $maxCarried = true;
        if ($constraints['spy_carries'] > 0) {
            // todo: refactor raw spies calculation
            $maxCarried = $this->militaryCalculator->getSpyRatioRaw($dominion) * $this->landCalculator->getTotalLand($dominion) * $constraints['spy_carries'];
        }

        // Techs
        $multiplier = (1 + $dominion->getTechPerkMultiplier('theft_gains') + $target->getTechPerkMultiplier('theft_losses'));

        return round(min($maxTarget, $maxDominion, $maxCarried) * $multiplier);
    }

    /**
     * @param Dominion $dominion
     * @param string $operationKey
     * @param Dominion $target
     * @return array
     * @throws Exception
     */
    protected function performHostileOperation(Dominion $dominion, string $operationKey, Dominion $target): array
    {
        if ($dominion->round->hasOffensiveActionsDisabled()) {
            throw new GameException('Black ops have been disabled for the remainder of the round.');
        }

        $operationInfo = $this->espionageHelper->getOperationInfo($operationKey);

        $warDeclared = $this->governmentService->isAtWar($dominion->realm, $target->realm);
        $blackGuard = $this->guardMembershipService->isBlackGuardMember($dominion) && $this->guardMembershipService->isBlackGuardMember($target);
        if ($this->espionageHelper->isWarOperation($operationKey)) {
            $recentlyInvaded = in_array($target->id, $this->militaryCalculator->getRecentlyInvadedBy($dominion, 12));
            if (!$warDeclared && !$recentlyInvaded) {
                if ($blackGuard) {
                    $this->guardMembershipService->checkLeaveApplication($dominion);
                } else {
                    throw new GameException("You cannot cast {$operationInfo['name']} outside of war.");
                }
            }
        }

        $selfSpa = $this->militaryCalculator->getSpyRatio($dominion, 'offense');
        $targetSpa = $this->militaryCalculator->getSpyRatio($target, 'defense');

        // You need at least some positive SPA to perform espionage operations
        if ($selfSpa == 0) {
            // Don't reduce spy strength by throwing an exception here
            throw new GameException("Your spy force is too weak to perform {$operationInfo['name']}. Please train some more spies.");
        }

        $successRate = $this->opsCalculator->blackOperationSuccessChance($selfSpa, $targetSpa, $dominion->spy_strength, $target->spy_strength);

        // Wonders
        $successRate *= (1 - $target->getWonderPerkMultiplier('enemy_espionage_chance'));

        if (!random_chance($successRate)) {
            list($unitsKilled, $unitsKilledString) = $this->handleLosses($dominion, $target, 'hostile');

            $this->notificationService
                ->queueNotification('repelled_spy_op', [
                    'sourceDominionId' => $dominion->id,
                    'operationKey' => $operationKey,
                    'unitsKilled' => $unitsKilledString,
                ])
                ->sendNotifications($target, 'irregular_dominion');

            if ($unitsKilledString) {
                $message = sprintf(
                    'The enemy has prevented our %s attempt and managed to capture %s.',
                    $operationInfo['name'],
                    $unitsKilledString
                );
            } else {
                $message = sprintf(
                    'The enemy has prevented our %s attempt.',
                    $operationInfo['name']
                );
            }

            return [
                'success' => false,
                'message' => $message,
                'alert-type' => 'warning',
            ];
        }

        $damageDealt = [];
        $totalDamage = 0;
        $baseDamage = (isset($operationInfo['percentage']) ? $operationInfo['percentage'] : 1) / 100;
        $baseDamageReductionMultiplier = $this->opsCalculator->getDamageReduction($target, 'spy');

        // Techs
        $baseDamageReductionMultiplier -= $target->getTechPerkMultiplier("enemy_{$operationInfo['key']}_damage");

        // Wonders
        $wonderDamagePerk = $target->getWonderPerkMultiplier("enemy_{$operationKey}_damage");
        if ($wonderDamagePerk == -1) {
            // Special case for damage immunity
            $baseDamage = 0;
        } else {
            $baseDamageReductionMultiplier -= $wonderDamagePerk;
        }

        // Assassinate Wizards damage reduction from Forest Havens
        if ($operationKey == 'assassinate_wizards') {
            $forestHavenAssassinateWizardReduction = 10;
            $forestHavenAssassinateWizardReductionMax = 50;
            $damageMultiplier = min(
                (($target->building_forest_haven / $this->landCalculator->getTotalLand($target)) * $forestHavenAssassinateWizardReduction),
                ($forestHavenAssassinateWizardReductionMax / 100)
            );
            $baseDamageReductionMultiplier += $damageMultiplier;
        }

        // Cap damage reduction at 80%
        $baseDamage *= (1 - min(0.8, $baseDamageReductionMultiplier));

        if (isset($operationInfo['decreases'])) {
            foreach ($operationInfo['decreases'] as $attr) {
                $damage = $target->{$attr} * $baseDamage;

                // Damage reduction from Docks / Harbor
                if ($attr == 'resource_boats') {
                    $boatsProtected = $this->militaryCalculator->getBoatsProtected($target);
                    $damage = max(0, floor($target->{$attr}) - $boatsProtected) * $baseDamage;
                }

                // Check for immortal wizards
                if ($target->race->getPerkValue('immortal_wizards') != 0 && $attr == 'military_wizards') {
                    $damage = 0;
                }

                if ($attr == 'wizard_strength') {
                    // Flat damage for Magic Snare
                    $damage = $baseDamage * 100;
                    if ($damage > $target->{$attr}) {
                        $damage = max(0, $target->{$attr});
                    }
                    $actualDamage = $damage;
                    $target->{$attr} -= $damage;
                    $damage = floor($target->{$attr}) - floor($target->{$attr} - $damage);
                } else {
                    // Rounded up for all other damage types
                    $damage = ceil($damage);
                    $actualDamage = $damage;
                    $target->{$attr} -= $damage;
                }

                $totalDamage += $actualDamage;
                $damageDealt[] = sprintf('%.3g %s', $actualDamage, dominion_attr_display($attr, $actualDamage));

                // Update statistics
                if (isset($dominion->{"stat_{$operationInfo['key']}_damage"})) {
                    $dominion->{"stat_{$operationInfo['key']}_damage"} += $damage;
                    $target->{"stat_{$operationInfo['key']}_damage_received"} += $damage;
                }
            }
        }
        if (isset($operationInfo['increases'])) {
            foreach ($operationInfo['increases'] as $attr) {
                $damage = round($target->{$attr} * $baseDamage);
                $target->{$attr} += $damage;
            }
        }

        $warRewardsString = '';
        if ($totalDamage > 0 && (
            $this->espionageHelper->isWarOperation($operationKey) ||
            ($this->espionageHelper->isBlackOperation($operationKey) && ($warDeclared || $blackGuard))
        )) {
            $results = $this->handleWarResults($dominion, $target, $operationKey);
            $warRewardsString = $results['warRewards'];
            if ($results['damageDealt'] !== '') {
                $damageDealt[] = $results['damageDealt'];
            }
        }

        // Surreal Perception
        $sourceDominionId = null;
        if ($target->getSpellPerkValue('surreal_perception') || $target->getWonderPerkValue('surreal_perception')) {
            $sourceDominionId = $dominion->id;
        }

        $damageString = generate_sentence_from_array($damageDealt);

        $this->notificationService
            ->queueNotification('received_spy_op', [
                'sourceDominionId' => $sourceDominionId,
                'operationKey' => $operationKey,
                'damageString' => $damageString,
            ])
            ->sendNotifications($target, 'irregular_dominion');

        return [
            'success' => true,
            'message' => sprintf(
                'Your spies infiltrate the target\'s dominion successfully, they lost %s. %s',
                $damageString,
                $warRewardsString
            ),
            'damage' => $totalDamage,
        ];
    }

    /**
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return array
     * @throws Exception
     */
    protected function handleLosses(Dominion $dominion, Dominion $target, string $type): array
    {
        $spiesKilledPercentage = $this->opsCalculator->getSpyLosses($dominion, $target, $type);
        $assassinsKilledPercentage = $this->opsCalculator->getAssassinLosses($dominion, $target, $type);
        // Cap losses by land size
        $totalLand = $this->landCalculator->getTotalLand($dominion);
        if ($type == 'info') {
            $spiesKilledCap = $totalLand * 0.006;
            $assassinsKilledCap = $totalLand * 0.003;
            $unitsKilledCap = $totalLand * 0.003;
        } else {
            $spiesKilledCap = $totalLand * 0.02;
            $assassinsKilledCap = $totalLand * 0.01;
            $unitsKilledCap = $totalLand * 0.01;
        }

        $spiesKilledModifier = 1;
        // Losses re-queued in Black Guard
        $blackGuard = $this->guardMembershipService->isBlackGuardMember($dominion) && $this->guardMembershipService->isBlackGuardMember($target);

        $unitsKilled = [];
        $spiesKilled = (int)floor($dominion->military_spies * $spiesKilledPercentage);
        $spiesKilled = round(min($spiesKilled, $spiesKilledCap) * $spiesKilledModifier);
        $assassinsKilled = (int)floor($dominion->military_assassins * $assassinsKilledPercentage);
        $assassinsKilled = round(min($assassinsKilled, $assassinsKilledCap) * $spiesKilledModifier);

        if ($spiesKilled > 0) {
            $unitsKilled['spies'] = $spiesKilled;
            $dominion->military_spies -= $spiesKilled;
            if ($blackGuard && $spiesKilled > 1) {
                $this->queueService->queueResources('training', $dominion, ['military_spies' => floor(0.75 * $spiesKilled)]);
            }
        }

        if ($assassinsKilled > 0) {
            $unitsKilled['assassins'] = $assassinsKilled;
            $dominion->military_assassins -= $assassinsKilled;
            if ($assassinsKilled > 1) {
                if ($blackGuard) {
                    $this->queueService->queueResources('training', $dominion, ['military_assassins' => floor(0.75 * $assassinsKilled)]);
                } elseif ($type == 'hostile') {
                    $this->queueService->queueResources('training', $dominion, ['military_spies' => floor(0.25 * $assassinsKilled)]);
                }
            }
        }

        foreach ($dominion->race->units as $unit) {
            if ($unit->getPerkValue('counts_as_spy_offense')) {
                $unitKilledMultiplier = ((float)$unit->getPerkValue('counts_as_spy_offense') / 2) * $spiesKilledPercentage;
                $unitKilled = (int)floor($dominion->{"military_unit{$unit->slot}"} * $unitKilledMultiplier);
                $unitKilled = round(min($unitKilled, $unitsKilledCap) * $spiesKilledModifier);
                if ($unitKilled > 0) {
                    $unitsKilled[strtolower($unit->name)] = $unitKilled;
                    $dominion->{"military_unit{$unit->slot}"} -= $unitKilled;
                    if ($blackGuard && $unitKilled > 1) {
                        $this->queueService->queueResources('training', $dominion, ["military_unit{$unit->slot}" => floor(0.75 * $unitKilled)]);
                    }
                }
            }
        }

        $target->stat_spies_executed += array_sum($unitsKilled);
        $dominion->stat_spies_lost += array_sum($unitsKilled);

        $unitsKilledStringParts = [];
        foreach ($unitsKilled as $name => $amount) {
            $amountLabel = number_format($amount);
            $unitLabel = str_plural(str_singular($name), $amount);
            $unitsKilledStringParts[] = "{$amountLabel} {$unitLabel}";
        }
        $unitsKilledString = generate_sentence_from_array($unitsKilledStringParts);

        return [$unitsKilled, $unitsKilledString];
    }

    /**
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $operationKey
     * @param float $modifier
     * @return array
     * @throws Exception
     */
    protected function handleWarResults(Dominion $dominion, Dominion $target, string $operationKey, float $modifier = 1): array
    {
        $damageDealtString = '';
        $warRewardsString = '';

        // Infamy and Resilience Gains
        $infamyGain = $this->opsCalculator->getInfamyGain($dominion, $target, 'spy', $modifier);
        if ($operationKey == 'magic_snare') {
            $resilienceGain = $this->opsCalculator->getResilienceGain($target, 'spy');
        } else {
            $resilienceGain = 0;
        }

        // Mutual War
        $mutualWarDeclared = $this->governmentService->isAtMutualWar($dominion->realm, $target->realm);
        if ($mutualWarDeclared) {
            $infamyGain = round(1.2 * $infamyGain);
            $resilienceGain = round(0.5 * $resilienceGain);
        }

        if ($dominion->infamy + $infamyGain > 1000) {
            $infamyGain = max(0, 1000 - $dominion->infamy);
        }
        $dominion->infamy += $infamyGain;
        $target->spy_resilience += $resilienceGain;

        // Mastery Gains
        $masteryGain = $this->opsCalculator->getMasteryGain($dominion, $target, 'spy', $modifier);
        $dominion->spy_mastery += $masteryGain;

        // Mastery Loss
        $masteryLoss = min($this->opsCalculator->getMasteryLoss($dominion, $target, 'spy'), $target->spy_mastery);
        $target->spy_mastery -= $masteryLoss;

        $warRewardsString = "You gained {$infamyGain} infamy and {$masteryGain} spy mastery.";
        if ($masteryLoss > 0) {
            $damageDealtString = "{$masteryLoss} spy mastery";
        }

        return [
            'damageDealt' => $damageDealtString,
            'warRewards' => $warRewardsString,
        ];
    }
}
