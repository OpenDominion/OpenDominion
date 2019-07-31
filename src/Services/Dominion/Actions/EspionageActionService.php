<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use LogicException;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\EspionageHelper;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\InfoOp;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\Queue\ConstructionQueueService;
use OpenDominion\Services\Dominion\Queue\ExplorationQueueService;
use OpenDominion\Services\Dominion\Queue\LandIncomingQueueService;
use OpenDominion\Services\Dominion\Queue\TrainingQueueService;
use OpenDominion\Services\Dominion\Queue\UnitsReturningQueueService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;
use Throwable;

class EspionageActionService
{
    use DominionGuardsTrait;

    /** @var BuildingHelper */
    protected $buildingHelper;

    /** @var EspionageHelper */
    protected $espionageHelper;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var ImprovementHelper */
    protected $improvementHelper;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var LandHelper */
    protected $landHelper;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var NotificationService */
    protected $notificationService;

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
        $this->buildingHelper = app(BuildingHelper::class);
        $this->espionageHelper = app(EspionageHelper::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->improvementHelper = app(ImprovementHelper::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->landHelper = app(LandHelper::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->notificationService = app(NotificationService::class);
        $this->productionCalculator = app(ProductionCalculator::class);
        $this->protectionService = app(ProtectionService::class);
        $this->queueService = app(QueueService::class);
        $this->rangeCalculator = app(RangeCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);
    }

    public const THEFT_DAYS_AFTER_ROUND_START = 7;

    /**
     * Performs a espionage operation for $dominion, aimed at $target dominion.
     *
     * @param Dominion $dominion
     * @param string $operationKey
     * @param Dominion $target
     * @return array
     * @throws Throwable
     */
    public function performOperation(Dominion $dominion, string $operationKey, Dominion $target): array
    {
        $this->guardLockedDominion($dominion);

        $operationInfo = $this->espionageHelper->getOperationInfo($operationKey);

        if (!$operationInfo) {
            throw new RuntimeException("Cannot perform unknown operation '{$operationKey}'");
        }

        if ($dominion->spy_strength < 30) {
            throw new RuntimeException("Your spies do not have enough strength to perform {$operationInfo['name']}.");
        }

        if ($this->protectionService->isUnderProtection($dominion)) {
            throw new RuntimeException('You cannot perform espionage operations while under protection');
        }

        if ($this->protectionService->isUnderProtection($target)) {
            throw new RuntimeException('You cannot perform espionage operations on targets which are under protection');
        }

        if (!$this->rangeCalculator->isInRange($dominion, $target)) {
            throw new RuntimeException('You cannot perform espionage operations on targets outside of your range');
        }

        if ($this->espionageHelper->isResourceTheftOperation($operationKey)) {
            if (now()->diffInDays($dominion->round->start_date) < self::THEFT_DAYS_AFTER_ROUND_START) {
                throw new RuntimeException('You cannot perform resource theft for the first seven days of the round');
            }
            if ($this->rangeCalculator->getDominionRange($dominion, $target) < 100) {
                throw new RuntimeException('You cannot perform resource theft on targets smaller than yourself');
            }
        }


        if ($dominion->round->id !== $target->round->id) {
            throw new RuntimeException('Nice try, but you cannot perform espionage operations cross-round');
        }

        if ($dominion->realm->id === $target->realm->id) {
            throw new RuntimeException('Nice try, but you cannot perform espionage oprations on your realmies');
        }

        $result = null;

        DB::transaction(function () use ($dominion, $operationKey, &$result, $target) {

            if ($this->espionageHelper->isInfoGatheringOperation($operationKey)) {
                $spyStrengthLost = 2;
                $result = $this->performInfoGatheringOperation($dominion, $operationKey, $target);
            } elseif ($this->espionageHelper->isResourceTheftOperation($operationKey)) {
                $spyStrengthLost = 5;
                $result = $this->performResourceTheftOperation($dominion, $operationKey, $target);
            } elseif ($this->espionageHelper->isBlackOperation($operationKey)) {
                throw new LogicException('Not yet implemented');
            } elseif ($this->espionageHelper->isWarOperation($operationKey)) {
                throw new LogicException('Not yet implemented');
            } else {
                throw new LogicException("Unknown type for espionage operation {$operationKey}");
            }

            $dominion->decrement('spy_strength', $spyStrengthLost);
            $dominion->save(['event' => HistoryService::EVENT_ACTION_PERFORM_ESPIONAGE_OPERATION, 'action' => $operationKey]);

        });

        return [
                'message' => $result['message'],
                'data' => [
                    'operation' => $operationKey,
                ],
                'redirect' =>
                    $this->espionageHelper->isInfoGatheringOperation($operationKey) && $result['success']
                        ? route('dominion.op-center.show', $target->id)
                        : null,
            ] + $result;
    }

    protected function performInfoGatheringOperation(Dominion $dominion, string $operationKey, Dominion $target): array
    {
        $operationInfo = $this->espionageHelper->getOperationInfo($operationKey);

        $selfSpa = $this->militaryCalculator->getSpyRatio($dominion, 'offense');
        $targetSpa = $this->militaryCalculator->getSpyRatio($target, 'defense');

        // You need at least some positive SPA to perform espionage operations
        if ($selfSpa === 0.0) {
            return [
                'success' => false,
                'message' => "Your spy force is too weak to cast {$operationInfo['name']}. Please train some more spies.",
                'alert-type' => 'warning',
            ];
        }

        if ($targetSpa !== 0.0) {
            $ratio = ($selfSpa / $targetSpa);

            // todo: copied from spell success ratio. needs looking into later
            // todo: factor in spy strength
            $successRate = clamp((
                (0.0172 * ($ratio ** 3))
                - (0.1809 * ($ratio ** 2))
                + (0.7777 * $ratio)
                - 0.0134
            ), 0.0, 1.0);

            if (!random_chance($successRate)) {
                // todo: move to CasualtiesCalculator

                // Values (percentage)
                $spiesKilledBasePercentage = 0.1; // TODO: Higher for black ops.
                $forestHavenSpyCasualtyReduction = 3;
                $forestHavenSpyCasualtyReductionMax = 30;

                $spiesKilledMultiplier = (1 - min(
                        (($dominion->building_forest_haven / $this->landCalculator->getTotalLand($dominion)) * $forestHavenSpyCasualtyReduction),
                        ($forestHavenSpyCasualtyReductionMax / 100)
                    ));

                $spyLossSpaRatio = ($targetSpa / $selfSpa);
                $spiesKilledPercentage = clamp($spiesKilledBasePercentage * $spyLossSpaRatio, 0.05, 0.5);

                // todo: check if we need to divide by lizzie chameleons (and other units that count at spies)?

                $spiesKilled = (int)floor(($dominion->military_spies * ($spiesKilledPercentage / 100)) * $spiesKilledMultiplier);
                $spiesKilled = min($spiesKilled, $dominion->military_spies); // Cap to amount of spies we have to prevent negatives, see issue #486

                $dominion->military_spies -= $spiesKilled;

                $this->notificationService
                    ->queueNotification('repelled_spy_op', [
                        'sourceDominionId' => $dominion->id,
                        'operationKey' => $operationKey,
                        'spiesKilled' => $spiesKilled,
                    ])
                    ->sendNotifications($target, 'irregular_dominion');

                if ($spiesKilled > 0) {
                    $message = ("The enemy has prevented our {$operationInfo['name']} attempt and managed to capture " . number_format($spiesKilled) . ' of our spies.');
                } else {
                    $message = "The enemy has prevented our {$operationInfo['name']} attempt.";
                }

                return [
                    'success' => false,
                    'message' => $message,
                    'alert-type' => 'warning',
                ];
            }
        }

        // todo: is not invalid?
        $infoOp = InfoOp::firstOrNew([
            'source_realm_id' => $dominion->realm->id,
            'target_dominion_id' => $target->id,
            'type' => $operationKey,
        ], [
            'source_dominion_id' => $dominion->id,
        ]);

        if ($infoOp->exists) {
            // Overwrite casted_by_dominion_id for the newer data
            $infoOp->source_dominion_id = $dominion->id;
        }

        switch ($operationKey) {
            case 'barracks_spy':
                $data = [
                    'units' => [
                        'home' => [],
                        'returning' => [],
                        'training' => [],
                    ],
                ];

                // Units at home (85% accurate)
                array_set($data, 'units.home.draftees', random_int(
                    round($target->military_draftees * 0.85),
                    round($target->military_draftees / 0.85)
                ));

                foreach (range(1, 4) as $slot) {
                    $amountAtHome = $target->{'military_unit' . $slot};

                    if ($amountAtHome !== 0) {
                        $amountAtHome = random_int(
                            round($amountAtHome * 0.85),
                            round($amountAtHome / 0.85)
                        );
                    }

                    array_set($data, "units.home.unit{$slot}", $amountAtHome);
                }

                // Units returning (85% accurate)
                $this->queueService->getInvasionQueue($target)->each(function ($row) use (&$data) {
                    if (!starts_with($row->resource, 'military_')) {
                        return; // continue
                    }

                    $unitType = str_replace('military_', '', $row->resource);

                    $amount = random_int(
                        round($row->amount * 0.85),
                        round($row->amount / 0.85)
                    );

                    array_set($data, "units.returning.{$unitType}.{$row->hours}", $amount);
                });

                // Units in training (100% accurate)
                $this->queueService->getTrainingQueue($target)->each(function ($row) use (&$data) {
                    $unitType = str_replace('military_', '', $row->resource);

                    array_set($data, "units.training.{$unitType}.{$row->hours}", $row->amount);
                });

                $infoOp->data = $data;
                break;

            case 'castle_spy':
                $data = [];

                foreach ($this->improvementHelper->getImprovementTypes() as $type) {
                    array_set($data, "{$type}.points", $target->{'improvement_' . $type});
                    array_set($data, "{$type}.rating",
                        $this->improvementCalculator->getImprovementMultiplierBonus($target, $type));
                }

                $infoOp->data = $data;
                break;

            case 'survey_dominion':
                $data = [];

                foreach ($this->buildingHelper->getBuildingTypes() as $buildingType) {
                    array_set($data, "constructed.{$buildingType}", $target->{'building_' . $buildingType});
                }

                $this->queueService->getConstructionQueue($target)->each(function ($row) use (&$data) {
                    $buildingType = str_replace('building_', '', $row->resource);

                    array_set($data, "constructing.{$buildingType}.{$row->hours}", $row->amount);
                });

                array_set($data, 'barren_land', $this->landCalculator->getTotalBarrenLand($target));

                $infoOp->data = $data;
                break;

            case 'land_spy':
                $data = [];

                foreach ($this->landHelper->getLandTypes() as $landType) {
                    $amount = $target->{'land_' . $landType};

                    array_set($data, "explored.{$landType}.amount", $amount);
                    array_set($data, "explored.{$landType}.percentage",
                        (($amount / $this->landCalculator->getTotalLand($target)) * 100));
                    array_set($data, "explored.{$landType}.barren",
                        $this->landCalculator->getTotalBarrenLandByLandType($target, $landType));
                }

                $this->queueService->getExplorationQueue($target)->each(function ($row) use (&$data) {
                    $landType = str_replace('land_', '', $row->resource);

                    array_set(
                        $data,
                        "incoming.{$landType}.{$row->hours}",
                        (array_get($data, "incoming.{$landType}.{$row->hours}", 0) + $row->amount)
                    );
                });

                $this->queueService->getInvasionQueue($target)->each(function ($row) use (&$data) {
                    if (!starts_with($row->resource, 'land_')) {
                        return; // continue
                    }

                    $landType = str_replace('land_', '', $row->resource);

                    array_set(
                        $data,
                        "incoming.{$landType}.{$row->hours}",
                        (array_get($data, "incoming.{$landType}.{$row->hours}", 0) + $row->amount)
                    );
                });

                $infoOp->data = $data;
                break;

            default:
                throw new LogicException("Unknown info gathering operation {$operationKey}");
        }

        // Always force update updated_at on infoops to know when the last infoop was performed
        $infoOp->updated_at = now(); // todo: fixable with ->save(['touch'])?
        $infoOp->save();

        if ($this->spellCalculator->isSpellActive($target, 'surreal_perception')) {
            $this->notificationService
                ->queueNotification('received_spy_op', [
                    'sourceDominionId' => $dominion->id,
                    'operationKey' => $operationKey,
                ])
                ->sendNotifications($target, 'irregular_dominion');
        }

        return [
            'success' => true,
            'message' => 'Your spies infiltrate the target\'s dominion successfully and return with a wealth of information.',
            'redirect' => route('dominion.op-center.show', $target),
        ];
    }

    protected function performResourceTheftOperation(Dominion $dominion, string $operationKey, Dominion $target): array
    {
        $operationInfo = $this->espionageHelper->getOperationInfo($operationKey);

        $selfSpa = $this->militaryCalculator->getSpyRatio($dominion, 'offense');
        $targetSpa = $this->militaryCalculator->getSpyRatio($target, 'defense');

        // You need at least some positive SPA to perform espionage operations
        if ($selfSpa === 0.0) {
            return [
                'success' => false,
                'message' => "Your spy force is too weak to cast {$operationInfo['name']}. Please train some more spies.",
                'alert-type' => 'warning',
            ];
        }

        if ($targetSpa !== 0.0) {
            $ratio = ($selfSpa / $targetSpa);

            // todo: copied from spell success ratio. needs looking into later
            // todo: factor in spy strength
            $successRate = clamp((
                (0.0172 * ($ratio ** 3))
                - (0.1809 * ($ratio ** 2))
                + (0.6767 * $ratio)
                - 0.0134
            ), 0.0, 1.0);

            if (!random_chance($successRate)) {
                // todo: move to CasualtiesCalculator

                // Values (percentage)
                $spiesKilledBasePercentage = 1;
                $forestHavenSpyCasualtyReduction = 3;
                $forestHavenSpyCasualtyReductionMax = 30;

                $spiesKilledMultiplier = (1 - min(
                        (($dominion->building_forest_haven / $this->landCalculator->getTotalLand($dominion)) * $forestHavenSpyCasualtyReduction),
                        ($forestHavenSpyCasualtyReductionMax / 100)
                    ));

                $spyLossSpaRatio = ($targetSpa / $selfSpa);
                $spiesKilledPercentage = clamp($spiesKilledBasePercentage * $spyLossSpaRatio, 0.05, 0.5);

                // todo: check if we need to divide by lizzie chameleons (and other units that count at spies)?

                $spiesKilled = (int)floor(($dominion->military_spies * ($spiesKilledPercentage / 100)) * $spiesKilledMultiplier);
                $spiesKilled = min($spiesKilled, $dominion->military_spies); // Cap to amount of spies we have to prevent negatives, see issue #486

                $dominion->military_spies -= $spiesKilled;

                $this->notificationService
                    ->queueNotification('repelled_resource_theft', [
                        'sourceDominionId' => $dominion->id,
                        'operationKey' => $operationKey,
                        'spiesKilled' => $spiesKilled,
                    ])
                    ->sendNotifications($target, 'irregular_dominion');

                if ($spiesKilled > 0) {
                    $message = ("The enemy has prevented our {$operationInfo['name']} attempt and managed to capture " . number_format($spiesKilled) . ' of our spies.');
                } else {
                    $message = "The enemy has prevented our {$operationInfo['name']} attempt.";
                }

                return [
                    'success' => false,
                    'message' => $message,
                    'alert-type' => 'warning',
                ];
            }
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
                    'self_production' => 0,
                    'spy_carries' => 0,
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
                    'self_production' => 0,
                    'spy_carries' => 50,
                ];
                break;

            default:
                throw new LogicException("Unknown resource theft operation {$operationKey}");
        }

        $amountStolen = $this->getResourceTheftAmount($dominion, $target, $resource, $constraints);

        DB::transaction(function () use ($dominion, $target, $resource, $amountStolen) {
            $dominion->increment("resource_{$resource}", $amountStolen);
            $dominion->save();

            $target->decrement("resource_{$resource}", $amountStolen);
            $target->save();
        });

        // Surreal Perception
        $sourceDominionId = null;
        if ($this->spellCalculator->isSpellActive($target, 'surreal_perception')) {
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
            'redirect' => route('dominion.op-center.show', $target),
        ];
    }

    protected function getResourceTheftAmount(Dominion $dominion, Dominion $target, string $resource, array $constraints): int
    {
        if ($resource == 'platinum' && $this->spellCalculator->isSpellActive($target, 'fools_gold'))
            return 0;

        // Limit to percentage of target's raw production
        $maxTarget = true;
        if ($constraints['target_amount'] > 0) {
            $maxTarget = $target->{'resource_' . $resource} * $constraints['target_amount'] / 100;
        }

        // Limit to percentage of dominion's raw production
        $maxDominion = true;
        if ($constraints['self_production'] > 0) {
            if ($resource == 'platinum')
                $maxDominion = floor($this->productionCalculator->getPlatinumProductionRaw($dominion) * $constraints['self_production'] / 100);
            elseif ($resource == 'food')
                $maxDominion = floor($this->productionCalculator->getFoodProductionRaw($dominion) * $constraints['self_production'] / 100);
            elseif ($resource == 'lumber')
                $maxDominion = floor($this->productionCalculator->getLumberProductionRaw($dominion) * $constraints['self_production'] / 100);
            elseif ($resource == 'mana')
                $maxDominion = floor($this->productionCalculator->getManaProductionRaw($dominion) * $constraints['self_production'] / 100);
            elseif ($resource == 'ore')
                $maxDominion = floor($this->productionCalculator->getOreProductionRaw($dominion) * $constraints['self_production'] / 100);
            elseif ($resource == 'gems')
                $maxDominion = floor($this->productionCalculator->getGemsProductionRaw($dominion) * $constraints['self_production'] / 100);
        }

        // Limit to amount carryable by spies
        $maxCarried = true;
        if ($constraints['spy_carries'] > 0) {
            // todo: refactor raw spies calculation
            $maxCarried = $this->militaryCalculator->getSpyRatioRaw($dominion) * $this->landCalculator->getTotalLand($dominion) * $constraints['spy_carries'];
        }

        // Forest Haven reduction
        if ($resource == 'platinum') {
            $forestHavenStolenPlatinumReduction = 8;
            $forestHavenStolenPlatinumReductionMax = 80;
            $stolenPlatinumMultiplier = (1 - min(
                    (($target->building_forest_haven / $this->landCalculator->getTotalLand($target)) * $forestHavenStolenPlatinumReduction),
                    ($forestHavenStolenPlatinumReductionMax / 100)
                ));
            $maxTarget = $maxTarget * $stolenPlatinumMultiplier;
        }

        return min($maxTarget, $maxDominion, $maxCarried);
    }

    // todo: black ops/war
    // don't forget that undead has immortal wizards
}
