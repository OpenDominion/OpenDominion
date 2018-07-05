<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use LogicException;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Helpers\EspionageHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\InfoOp;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;
use Throwable;

class EspionageActionService
{
    use DominionGuardsTrait;

    /** @var EspionageHelper */
    protected $espionageHelper;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /**
     * EspionageActionService constructor.
     *
     * @param EspionageHelper $espionageHelper
     * @param MilitaryCalculator $militaryCalculator
     */
    public function __construct(EspionageHelper $espionageHelper, MilitaryCalculator $militaryCalculator)
    {
        $this->espionageHelper = $espionageHelper;
        $this->militaryCalculator = $militaryCalculator;
    }

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
            throw new RuntimeException("Your spies to not have enough strength to perform {$operationInfo['name']}.");
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
                $result = $this->performInfoGatheringOperation($dominion, $operationKey, $target);

            } elseif ($this->espionageHelper->isResourceTheftOperation($operationKey)) {
                throw new LogicException('Not yet implemented');

            } elseif ($this->espionageHelper->isBlackOperation($operationKey)) {
                throw new LogicException('Not yet implemented');

            } elseif ($this->espionageHelper->isWarOperation($operationKey)) {
                throw new LogicException('Not yet implemented');

            } else {
                throw new LogicException("Unknown type for espionage operation {$operationKey}");
            }

            $dominion->spy_strength -= 2; // todo: different values for different kind of ops (info ops 2%, rest 5%)
            $dominion->save(['event' => HistoryService::EVENT_ACTION_PERFORM_ESPIONAGE_OPERATION]);

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

        $selfSpa = $this->militaryCalculator->getSpyRatio($dominion);
        $targetSpa = $this->militaryCalculator->getSpyRatio($target);

        // You need at least some positive SPA to perform espionage operations
        if ($selfSpa === 0.0) {
            return [
                'success' => false,
                'message' => "Your spy force is too weak to cast {$operationInfo['name']}. Please train some more spies.",
                'alert-type' => 'warning',
            ];
        }

        if ($targetSpa !== 1.0) {
            $ratio = ($selfSpa / $targetSpa);

            // todo: copied from spell success ratio. needs looking into later
            // todo: factor in spy strength
            $successRate = (
                (0.0172 * ($ratio ** 3))
                - (0.1809 * ($ratio ** 2))
                + (0.6767 * $ratio)
                - 0.0134
            );

            if (!random_chance($successRate)) {
                // todo: have some spies captured and killed

//                return [
//                    'success' => false,
//                    'message' => "The enemy spies have repelled our {$operationInfo['name']} attempt.",
//                    'alert-type' => 'warning',
//                ];
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
//                $data = [];
                $data = $infoOp->data;

                foreach (range(0, 3) as $i) {
                    $amount = $target->{'military_unit' . $i};

//                    dd(
//                        array_get($data, "units.{$i}.exact"),
//                        $amount
//                    );

                    if (array_get($data, "units.{$i}.exact") !== $amount) {

                    }

                    array_set($data, "units.{$i}.exact", $amount);


                    $randomizedAmount = random_int(
                        round($amount * 0.85),
                        round($amount / 0.85)
                    );

                    array_set($data, "units.{$i}", $randomizedAmount);
                }

                $infoOp->data = $data;
                break;

            case 'castle_spy':
                break;

            case 'survey_dominion':
                break;

            case 'land_spy':
                break;

            default:
                throw new LogicException("Unknown info gathering operation {$operationKey}");
        }

        dd($infoOp->toArray()['data']['units']);

        // Always force update updated_at on infoops to know when the last infoop was performed
        $infoOp->updated_at = now();
        $infoOp->save();

        return [
            'success' => true,
            'message' => 'Your spies infiltrate the target\'s dominion successfully and return with a wealth of information.',
            'redirect' => route('dominion.op-center.show', $target),
        ];
    }

    // todo: theft/black ops/war
}
