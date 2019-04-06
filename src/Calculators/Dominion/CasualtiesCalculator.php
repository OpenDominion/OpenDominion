<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;

class CasualtiesCalculator
{
    /** @var LandCalculator */
    protected $landCalculator;

    /** @var UnitHelper */
    protected $unitHelper;

    /**
     * CasualtiesCalculator constructor.
     *
     * @param LandCalculator $landCalculator
     * @param UnitHelper $unitHelper
     */
    public function __construct(LandCalculator $landCalculator, UnitHelper $unitHelper)
    {
        $this->landCalculator = $landCalculator;
        $this->unitHelper = $unitHelper;
    }

    /**
     * Get the offensive casualty multiplier for a dominion for a specific unit
     * slot.
     *
     * @param Dominion $dominion
     * @param int $slot
     * @param array $units Units being sent out on invasion
     * @param bool $isOverwhelmed
     * @return float
     */
    public function getOffensiveCasualtiesMultiplierForUnitSlot(Dominion $dominion, int $slot, array $units, bool $isOverwhelmed): float
    {
        $multiplier = 1;

        // First check immortality, so we can skip the other checks on immortal
        // units
        // Note: Immortality only works if you're NOT overwhelmed, regardless if
        // invasion is successful or not
        if (!$isOverwhelmed && $dominion->race->getUnitPerkValueForUnitSlot($slot, 'immortal')) {
            // todo: check HuNo's Crusader vs SPUD
            $multiplier = 0;

        } elseif (!$isOverwhelmed && $dominion->race->getUnitPerkValueForUnitSlot($slot, 'immortal_except_vs_icekin')) {
            // todo: check more immortal_except_vs_*
            // todo: icekin isn't implemented yet. Once I do, refactor this
            $multiplier = 0;
        }

        if ($multiplier !== 0) {
            // Non-unit bonuses (hero, shrines, tech, wonders), capped at -80%
            $nonUnitBonusMultiplier = 0;

            // todo: Heroes

            // Shrines
            $nonUnitBonusMultiplier += $this->getOffensiveCasualtiesReductionFromShrines($dominion);

            // todo: Tech (eg Tactical Battle)

            // todo: Wonders

            // Cap at -80% and apply to multiplier (additive)
            $multiplier -= min(0.8, $nonUnitBonusMultiplier);

            // Unit bonuses (multiplicative with non-unit bonuses)
            $unitBonusMultiplier = 0;

            // Unit Perk: Fewer Casualties
            $unitBonusMultiplier += ($dominion->race->getUnitPerkValueForUnitSlot($slot, ['fewer_casualties', 'fewer_casualties_offense']) / 100);

            // Unit Perk: Reduce Combat Losses
            $unitsAtHomePerSlot = [];
            $unitsAtHomeRCLSlot = null;
            $reducedCombatLossesMultiplierAddition = 0;

            // todo: inefficient to do run this code per slot. needs refactoring
            foreach ($dominion->race->units as $unit) {
                $slot = $unit->slot;
                $unitKey = "military_unit{$slot}";

                $totalUnitAmount = $dominion->$unitKey;

                $unitsAtHome = ($totalUnitAmount - ($units[$slot] ?? 0));
                $unitsAtHomePerSlot[$slot] = $unitsAtHome;

                if ($unit->perkType === null) {
                    continue;
                }

                if (($unit->perkType->key === 'reduced_combat_losses')) {
                    $unitsAtHomeRCLSlot = $slot;
                }
            }

            // We have a unit with RCL!
            if ($unitsAtHomeRCLSlot !== null) {
                $totalUnitsAtHome = array_sum($unitsAtHomePerSlot);

                $reducedCombatLossesMultiplierAddition += (($unitsAtHomePerSlot[$unitsAtHomeRCLSlot] / $totalUnitsAtHome) / 2);
            }

            $unitBonusMultiplier += $reducedCombatLossesMultiplierAddition;

            // todo: Troll/Orc unit perks, possibly other perks elsewhere too

            // Apply to multiplier (multiplicative)
            $multiplier *= (1 - $unitBonusMultiplier);
        }

        return $multiplier;
    }

    /**
     * Get the defensive casualty multiplier for a dominion for a specific unit
     * slot.
     *
     * @param Dominion $dominion
     * @param int|null $slot Null is for non-racial units and thus used as draftees casualties multiplier
     * @return float
     */
    public function getDefensiveCasualtiesMultiplierForUnitSlot(Dominion $dominion, ?int $slot): float
    {
        $multiplier = 1;

        // Non-unit bonuses (hero, tech, wonders), capped at -80%
//        $nonUnitBonusMultiplier = 0;

        // todo: Heroes

        // todo: Tech

        // todo: Wonders

        // Cap at -80% and apply to multiplier (additive)
//        $multiplier -= min(0.8, $nonUnitBonusMultiplier);

        // Unit bonuses (multiplicative with non-unit bonuses)
        $unitBonusMultiplier = 0;

        // Unit Perk: Fewer Casualties (only on military units with a slot, draftees don't have this perk)
        if ($slot) {
            $unitBonusMultiplier += ($dominion->race->getUnitPerkValueForUnitSlot($slot, ['fewer_casualties', 'fewer_casualties_defense']) / 100);
        }

        // Unit Perk: Reduce Combat Losses
        $unitsAtHomePerSlot = [];
        $unitsAtHomeRCLSlot = null;
        $reducedCombatLossesMultiplierAddition = 0;

        // todo: inefficient to do run this code per slot. needs refactoring
        foreach ($dominion->race->units as $unit) {
            $slot = $unit->slot;
            $unitKey = "military_unit{$slot}";

            $unitsAtHomePerSlot[$slot] = $dominion->$unitKey;

            if ($unit->perkType === null) {
                continue;
            }

            if (($unit->perkType->key === 'reduced_combat_losses')) {
                $unitsAtHomeRCLSlot = $slot;
            }
        }

        // We have a unit with RCL!
        if ($unitsAtHomeRCLSlot !== null) {
            $totalUnitsAtHome = array_sum($unitsAtHomePerSlot);

            $reducedCombatLossesMultiplierAddition += (($unitsAtHomePerSlot[$unitsAtHomeRCLSlot] / $totalUnitsAtHome) / 2);
        }

        $unitBonusMultiplier += $reducedCombatLossesMultiplierAddition;

        // todo: Troll/Orc unit perks, possibly other perks elsewhere too

        // Apply to multiplier (multiplicative)
        $multiplier *= (1 - $unitBonusMultiplier);

        return $multiplier;
    }

    /**
     * Returns the Dominion's offensive casualties reduction from shrines.
     *
     * This number is in the 0 - 0.8 range, where 0 is no casualty reduction
     * (0%) and 0.8 is full (-80%). Used additive in a multiplier formula.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOffensiveCasualtiesReductionFromShrines(Dominion $dominion): float
    {
        // Values (percentage)
        $casualtyReductionPerShrine = 4;
        $maxCasualtyReductionFromShrines = 80;

        return min(
            (($casualtyReductionPerShrine * $dominion->building_shrine) / $this->landCalculator->getTotalLand($dominion)),
            ($maxCasualtyReductionFromShrines / 100)
        );
    }

    /**
     * Returns the Dominion's casualties by unit type.
     *
     * @param  Dominion $dominion
     * @return array
     */
    public function getStarvationCasualtiesByUnitType(Dominion $dominion): array
    {
        $units = $this->getStarvationUnitTypes();

        $totalCasualties = $this->getTotalStarvationCasualties($dominion);

        if ($totalCasualties === 0) {
            return [];
        }

        $casualties = ['peasants' => min($totalCasualties / 2, $dominion->peasants)];
        $casualties += array_fill_keys($units, 0);

        $remainingCasualties = ($totalCasualties - array_sum($casualties));

        while (count($units) > 0 && $remainingCasualties > 0) {
            foreach ($units as $unit) {
                $casualties[$unit] = (int)min(
                    (array_get($casualties, $unit, 0) + (int)(ceil($remainingCasualties / count($units)))),
                    $dominion->{$unit}
                );
            }

            $remainingCasualties = $totalCasualties - array_sum($casualties);

            $units = array_filter($units, function ($unit) use ($dominion, $casualties) {
                return ($casualties[$unit] < $dominion->{$unit});
            });
        }

        if ($remainingCasualties < 0) {
            while ($remainingCasualties < 0) {
                foreach (array_keys(array_reverse($casualties)) as $unitType) {
                    if ($casualties[$unitType] > 0) {
                        $casualties[$unitType]--;
                        $remainingCasualties++;
                    }

                    if ($remainingCasualties === 0) {
                        break 2;
                    }
                }
            }
        } elseif ($remainingCasualties > 0) {
            $casualties['peasants'] = (int)min(
                ($remainingCasualties + $casualties['peasants']),
                $dominion->peasants
            );
        }

        return array_filter($casualties);
    }

    /**
     * Returns the Dominion's number of casualties due to starvation.
     *
     * @param  Dominion $dominion
     * @return int
     */
    public function getTotalStarvationCasualties(Dominion $dominion): int
    {
        if ($dominion->resource_food >= 0) {
            return 0;
        }

        return (int)(abs($dominion->resource_food) * 4);
    }

    /**
     * Returns the unit types that can suffer casualties.
     *
     * @return array
     */
    protected function getStarvationUnitTypes(): array
    {
        return array_merge(
            array_map(
                function ($unit) {
                    return ('military_' . $unit);
                },
                $this->unitHelper->getUnitTypes()
            ),
            ['military_draftees']
        );
    }
}
