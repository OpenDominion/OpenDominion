<?php

namespace OpenDominion\Services\Dominion\Actions\Military;

use DB;
use OpenDominion\Calculators\Dominion\Actions\TrainingCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Traits\DominionGuardsTrait;
use Throwable;

class TrainActionService
{
    use DominionGuardsTrait;

    /** @var QueueService */
    protected $queueService;

    /** @var TrainingCalculator */
    protected $trainingCalculator;

    /** @var UnitHelper */
    protected $unitHelper;

    /**
     * TrainActionService constructor.
     */
    public function __construct()
    {
        $this->queueService = app(QueueService::class);
        $this->trainingCalculator = app(TrainingCalculator::class);
        $this->unitHelper = app(UnitHelper::class);
    }

    /**
     * Does a military train action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $data
     * @return array
     * @throws Throwable
     */
    public function train(Dominion $dominion, array $data): array
    {
        $this->guardLockedDominion($dominion);
        $this->guardActionsDuringTick($dominion);

        $data = array_only($data, array_map(function ($value) {
            return "military_{$value}";
        }, $this->unitHelper->getUnitTypes()));

        $data = array_map('\intval', $data);

        $totalUnitsToTrain = array_sum($data);

        if ($totalUnitsToTrain <= 0) {
            throw new GameException('Training aborted due to bad input.');
        }

        $totalCosts = [
            'platinum' => 0,
            'ore' => 0,
            'mana' => 0,
            'lumber' => 0,
            'gems' => 0,
            'draftees' => 0,
            'spies' => 0,
            'wizards' => 0,
        ];

        $unitsToTrain = [];

        $trainingCostsPerUnit = $this->trainingCalculator->getTrainingCostsPerUnit($dominion);

        foreach ($data as $unitType => $amountToTrain) {
            if (!$amountToTrain || $amountToTrain === 0) {
                continue;
            }

            if ($amountToTrain < 0) {
                throw new GameException('Training aborted due to bad input.');
            }

            $unitType = str_replace('military_', '', $unitType);

            $costs = $trainingCostsPerUnit[$unitType];

            foreach ($costs as $costType => $costAmount) {
                $totalCosts[$costType] += ($amountToTrain * $costAmount);
            }

            $unitsToTrain[$unitType] = $amountToTrain;
        }

        if (
            $totalCosts['platinum'] > $dominion->resource_platinum ||
            $totalCosts['ore'] > $dominion->resource_ore ||
            $totalCosts['mana'] > $dominion->resource_mana ||
            $totalCosts['lumber'] > $dominion->resource_lumber ||
            $totalCosts['gems'] > $dominion->resource_gems
        ) {
            throw new GameException('Training aborted due to lack of economical resources');
        }

        if ($totalCosts['draftees'] > $dominion->military_draftees) {
            throw new GameException('Training aborted due to lack of draftees');
        }

        if ($totalCosts['spies'] > $dominion->military_spies) {
            throw new GameException('Training aborted due to lack of spies');
        }

        if ($totalCosts['wizards'] > $dominion->military_wizards) {
            throw new GameException('Training aborted due to lack of wizards');
        }

        // Specialists train in 9 hours
        $nineHourData = [
            'military_unit1' => array_get($data, 'military_unit1', 0),
            'military_unit2' => array_get($data, 'military_unit2', 0),
        ];
        unset($data['military_unit1'], $data['military_unit2']);

        DB::transaction(function () use ($dominion, $totalCosts, $data, $nineHourData) {
            $this->queueService->queueResources('training', $dominion, $nineHourData, 9);
            $this->queueService->queueResources('training', $dominion, $data);
            $dominion->resource_platinum -= $totalCosts['platinum'];
            $dominion->resource_ore -= $totalCosts['ore'];
            $dominion->resource_mana -= $totalCosts['mana'];
            $dominion->resource_lumber -= $totalCosts['lumber'];
            $dominion->resource_gems -= $totalCosts['gems'];
            $dominion->military_draftees -= $totalCosts['draftees'];
            $dominion->military_spies -= $totalCosts['spies'];
            $dominion->military_wizards -= $totalCosts['wizards'];
            $dominion->stat_total_platinum_spent_training += $totalCosts['platinum'];
            $dominion->stat_total_ore_spent_training += $totalCosts['ore'];
            $dominion->stat_total_mana_spent_training += $totalCosts['mana'];
            $dominion->stat_total_lumber_spent_training += $totalCosts['lumber'];
            $dominion->stat_total_gems_spent_training += $totalCosts['gems'];
            $dominion->save([
                'event' => HistoryService::EVENT_ACTION_TRAIN,
                'queue' => ['training' => array_filter($nineHourData + $data)]
            ]);
        });

        return [
            'message' => $this->getReturnMessageString($dominion, $unitsToTrain, $totalCosts),
            'data' => [
                'totalCosts' => $totalCosts,
            ],
        ];
    }

    /**
     * Returns the message for a train action.
     *
     * @param Dominion $dominion
     * @param array $unitsToTrain
     * @param array $totalCosts
     * @return string
     */
    protected function getReturnMessageString(Dominion $dominion, array $unitsToTrain, array $totalCosts): string
    {
        $unitsToTrainStringParts = [];

        foreach ($unitsToTrain as $unitType => $amount) {
            if ($amount > 0) {
                $unitName = strtolower($this->unitHelper->getUnitName($unitType, $dominion->race));

                // str_plural() isn't perfect for certain unit names. This array
                // serves as an override to use (see issue #607)
                // todo: Might move this to UnitHelper, especially if more
                //       locations need unit name overrides
                $overridePluralUnitNames = [
                    'shaman' => 'shamans',
                ];

                $amountLabel = number_format($amount);

                if (array_key_exists($unitName, $overridePluralUnitNames)) {
                    if ($amount === 1) {
                        $unitLabel = $unitName;
                    } else {
                        $unitLabel = $overridePluralUnitNames[$unitName];
                    }
                } else {
                    $unitLabel = str_plural(str_singular($unitName), $amount);
                }

                $unitsToTrainStringParts[] = "{$amountLabel} {$unitLabel}";
            }
        }

        $unitsToTrainString = generate_sentence_from_array($unitsToTrainStringParts);

        $trainingCostsStringParts = [];
        foreach ($totalCosts as $costType => $cost) {
            if ($cost === 0) {
                continue;
            }

            $costType = str_singular($costType);
            if (!\in_array($costType, ['platinum', 'ore', 'mana', 'lumber', 'gems'], true)) {
                $costType = str_plural($costType, $cost);
            }
            $trainingCostsStringParts[] = (number_format($cost) . ' ' . $costType);

        }

        $trainingCostsString = generate_sentence_from_array($trainingCostsStringParts);

        $message = sprintf(
            'Training of %s begun at a cost of %s.',
            $unitsToTrainString,
            $trainingCostsString
        );

        return $message;
    }
}
