<?php

namespace OpenDominion\Services\Dominion\Actions;

use Illuminate\Support\Str;
use OpenDominion\Calculators\Dominion\Actions\ConstructionCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Traits\DominionGuardsTrait;

class DestroyActionService
{
    use DominionGuardsTrait;

    /** @var ConstructionCalculator */
    protected $constructionCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var ProtectionService */
    protected $protectionService;

    /**
     * DestroyActionService constructor.
     */
    public function __construct()
    {
        $this->constructionCalculator = app(ConstructionCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->protectionService = app(ProtectionService::class);
    }

    /**
     * Does a destroy buildings action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $data
     * @return array
     * @throws GameException
     */
    public function destroy(Dominion $dominion, array $data): array
    {
        $this->guardLockedDominion($dominion);

        $data = array_map('\intval', $data);

        $discountedAcres = 0;
        $totalBuildingsToDestroy = array_sum($data);

        if ($totalBuildingsToDestroy === 0) {
            throw new GameException('The destruction was not completed due to bad input.');
        }

        // Check for excessive DP reduction
        $defensiveMultiplier = $this->militaryCalculator->getDefensivePowerMultiplier($dominion);
        $defenseBeforeDestroy = $this->militaryCalculator->getDefensivePowerRaw($dominion);
        $defenseBeforeDestroy *= $defensiveMultiplier;

        foreach ($data as $buildingType => $amount) {
            if ($amount === 0) {
                continue;
            }

            if ($amount < 0) {
                throw new GameException('Destruction was not completed due to bad input.');
            }

            if ($amount > $dominion->{'building_' . $buildingType}) {
                throw new GameException('The destruction was not completed due to bad input.');
            }
        }

        foreach ($data as $buildingType => $amount) {
            $dominion->{'building_' . $buildingType} -= $amount;
            // Heroes
            if ($dominion->hero !== null) {
                if ($dominion->hero->getPerkValue('raze_mod_building_discount') && in_array($buildingType, ['dock', 'gryphon_nest', 'guard_tower', 'smithy', 'temple'])) {
                    $discountedAcres += $amount;
                }
            }
        }

        // Check for excessive DP reduction
        $defensiveMultiplier = $this->militaryCalculator->getDefensivePowerMultiplier($dominion);
        $defenseAfterDestroy = $this->militaryCalculator->getDefensivePowerRaw($dominion);
        $defenseAfterDestroy *= $defensiveMultiplier;
        $defenseReduced = $defenseBeforeDestroy - $defenseAfterDestroy;

        if ($defenseReduced > 0 && !$this->protectionService->isUnderProtection($dominion)) {
            $defenseReducedRecently = $this->militaryCalculator->getDefenseReducedRecently($dominion);
            if ((($defenseReduced + $defenseReducedRecently) / ($defenseBeforeDestroy + $defenseReducedRecently)) > 0.15) {
                throw new GameException('You cannot reduce your defense by more than 15% during a 24 hour period.');
            }
        }

        $destructionRefundString = '';
        if ($dominion->getTechPerkValue('destruction_refund') != 0) {
            $multiplier = $dominion->getTechPerkMultiplier('destruction_refund');

            $platinumCost = round($this->constructionCalculator->getPlatinumCostRaw($dominion) * $multiplier);
            $lumberCost= round($this->constructionCalculator->getLumberCostRaw($dominion) * $multiplier);

            // Can never get more per acre than the current modded cost per acre
            $platinumCost = min($platinumCost, $this->constructionCalculator->getPlatinumCost($dominion));
            $lumberCost = min($lumberCost, $this->constructionCalculator->getLumberCost($dominion));

            $platinumRefund = round($platinumCost * $totalBuildingsToDestroy);
            $lumberRefund = round($lumberCost * $totalBuildingsToDestroy);

            $destructionRefundString = " You were refunded {$platinumRefund} platinum and {$lumberRefund} lumber.";
            $dominion->resource_platinum += $platinumRefund;
            $dominion->resource_lumber += $lumberRefund;
        }

        $excludedRaces = ['nomad-rework', 'wood-elf'];
        if ($dominion->getTechPerkValue('destruction_discount') != 0 && !in_array($dominion->race->key, $excludedRaces)) {
            $multiplier = $dominion->getTechPerkMultiplier('destruction_discount');
            $discountedAcres = rfloor($multiplier * $totalBuildingsToDestroy);
        }

        if ($discountedAcres > 0) {
            $dominion->discounted_land += $discountedAcres;
            $destructionRefundString = " {$discountedAcres} acres can now be rebuilt at a discount.";
        }

        $dominion->save([
            'event' => HistoryService::EVENT_ACTION_DESTROY,
            'defense_reduced' => $defenseReduced
        ]);

        return [
            'message' => sprintf(
                'Destruction of %s %s is complete.%s',
                number_format($totalBuildingsToDestroy),
                Str::plural('building', $totalBuildingsToDestroy),
                $destructionRefundString
            ),
            'data' => [
                'totalBuildingsDestroyed' => $totalBuildingsToDestroy,
            ],
        ];
    }
}
