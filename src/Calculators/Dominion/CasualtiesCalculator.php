<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;

class CasualtiesCalculator
{
    /** @var HeroCalculator */
    protected $heroCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var PopulationCalculator */
    private $populationCalculator;

    /** @var UnitHelper */
    protected $unitHelper;

    /**
     * CasualtiesCalculator constructor.
     */
    public function __construct()
    {
        $this->heroCalculator = app(HeroCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->unitHelper = app(UnitHelper::class);
    }

    /**
     * Get the offensive casualty multiplier for a dominion for a specific unit
     * slot.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param int $slot
     * @param array $units Units being sent out on invasion
     * @param float $landRatio
     * @return float
     */
    public function getOffensiveCasualtiesMultiplierForUnitSlot(Dominion $dominion, Dominion $target, int $slot, array $units, float $landRatio): float
    {
        $multiplier = 1;

        // Check if unit has fixed casualties first, so we can skip all other checks
        if ($dominion->race->getUnitPerkValueForUnitSlot($slot, 'fixed_casualties') !== 0) {
            return $multiplier;
        }

        // Wonders
        if ($target->getWonderPerkValue('max_casualties_offense')) {
            return $multiplier;
        }

        // Spells
        if ($dominion->getSpellPerkValue('cancels_immortal')) {
            return $multiplier;
        }

        // Then check immortality, so we can skip the other remaining checks if we indeed have immortal units, since
        // casualties will then always be 0 anyway

        // Global immortality
        if ($dominion->race->getUnitPerkValueForUnitSlot($slot, 'immortal') !== 0) {
            $multiplier = 0;
        }

        // Range-based immortality
        $immortalVsLandRange = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'immortal_vs_land_range');
        if ($immortalVsLandRange !== 0 && $landRatio >= ($immortalVsLandRange / 100)) {
            $multiplier = 0;
        }

        // Race perk-based immortality
        if ($this->isImmortalVersusRacePerk($dominion, $target, $slot)) {
            $multiplier = 0;
        }

        if ($multiplier == 0) {
            // Unit Perk: Kills Immortal
            $unitsAtHomePerSlot = [];
            $unitsAtHomeKISlot = null;
            $totalUnitsAtHome = 0;

            // todo: inefficient to do run this code per slot. needs refactoring
            foreach ($target->race->units as $unit) {
                $slot = $unit->slot;
                $unitKey = "military_unit{$slot}";

                $unitsAtHomePerSlot[$slot] = $target->{$unitKey};
                if ($unit->power_defense > 0) {
                    $totalUnitsAtHome += $target->{$unitKey};
                }

                if ($unit->getPerkValue('kills_immortal') !== 0) {
                    $unitsAtHomeKISlot = $slot;
                }
            }

            // We have a unit with KI!
            if ($unitsAtHomeKISlot !== null && $totalUnitsAtHome > 0) {
                $multiplier = ($unitsAtHomePerSlot[$unitsAtHomeKISlot] / $totalUnitsAtHome);
            }
        }

        // Wonders
        if ($target->getWonderPerkValue('kills_immortal')) {
            $multiplier = 1;
        }

        if ($multiplier !== 0) {
            // Non-unit bonuses
            $nonUnitBonusMultiplier = $this->getOffensiveCasualtiesMultiplier($dominion);

            // Wonders
            $nonUnitBonusMultiplier += $target->getWonderPerkMultiplier('enemy_casualties_offense');

            $multiplier *= $nonUnitBonusMultiplier;

            // Unit bonuses (multiplicative with non-unit bonuses)
            $unitBonusMultiplier = 0;

            // Unit Perk: Fewer Casualties
            $unitBonusMultiplier -= ($dominion->race->getUnitPerkValueForUnitSlot($slot, ['casualties', 'casualties_offense']) / 100);
            if ($landRatio >= 0.75) {
                $unitBonusMultiplier -= ($dominion->race->getUnitPerkValueForUnitSlot($slot, 'casualties_offense_range') / 100);
            }

            // Unit Perk: Reduce Combat Losses
            $unitsSentPerSlot = [];
            $unitsSentRCLSlot = null;
            $reducedCombatLossesMultiplierAddition = 0;

            // todo: inefficient to do run this code per slot. needs refactoring
            foreach ($dominion->race->units as $unit) {
                $slot = $unit->slot;

                if (!isset($units[$slot])) {
                    continue;
                }
                $unitsSentPerSlot[$slot] = $units[$slot];

                if ($unit->getPerkValue('reduce_combat_losses') !== 0) {
                    $unitsSentRCLSlot = $slot;
                }
            }

            // We have a unit with RCL!
            if ($unitsSentRCLSlot !== null) {
                $totalUnitsSent = array_sum($unitsSentPerSlot);

                $reducedCombatLossesMultiplierAddition += (($unitsSentPerSlot[$unitsSentRCLSlot] / $totalUnitsSent) / 2);
            }

            $unitBonusMultiplier += $reducedCombatLossesMultiplierAddition;

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
     * @param Dominion $attacker
     * @param int|null $slot Null is for non-racial units and thus used as draftees casualties multiplier
     * @return float
     */
    public function getDefensiveCasualtiesMultiplierForUnitSlot(Dominion $dominion, Dominion $attacker, ?int $slot, ?array $units): float
    {
        $multiplier = 1;

        // Wonders
        if ($attacker->getWonderPerkValue('max_casualties_defense')) {
            return $multiplier;
        }

        // Spells
        if ($dominion->getSpellPerkValue('cancels_immortal')) {
            return $multiplier;
        }

        // First check immortality, so we can skip the other remaining checks if we indeed have immortal units, since
        // casualties will then always be 0 anyway

        // Only military units with a slot number could be immortal
        if ($slot !== null) {
            // Global immortality
            if ((bool)$dominion->race->getUnitPerkValueForUnitSlot($slot, 'immortal')) {
                $multiplier = 0;

                // Spells
                if ($attacker->getSpellPerkValue('kills_immortal')) {
                    $multiplier = 1;
                }
            }

            if ($multiplier == 0) {
                // Unit Perk: Kills Immortal
                $unitsSentPerSlot = [];
                $unitsSentKISlot = null;

                // todo: inefficient to do run this code per slot. needs refactoring
                foreach ($attacker->race->units as $unit) {
                    $slot = $unit->slot;

                    if (!isset($units[$slot])) {
                        continue;
                    }
                    $unitsSentPerSlot[$slot] = $units[$slot];

                    if ($unit->getPerkValue('kills_immortal') !== 0) {
                        $unitsSentKISlot = $slot;
                    }
                }

                // We have a unit with KI!
                if ($unitsSentKISlot !== null) {
                    $totalUnitsSent = array_sum($unitsSentPerSlot);
                    $multiplier = ($unitsSentPerSlot[$unitsSentKISlot] / $totalUnitsSent);
                }
            }

            // Race perk-based immortality
            if (($multiplier !== 0) && $this->isImmortalVersusRacePerk($dominion, $attacker, $slot)) {
                $multiplier = 0;
            }
        }

        // Wonders
        if ($attacker->getWonderPerkValue('kills_immortal')) {
            $multiplier = 1;
        }

        if ($multiplier !== 0) {
            // Non-unit bonuses
            $nonUnitBonusMultiplier = $this->getDefensiveCasualtiesMultiplier($dominion);

            // Wonders
            $nonUnitBonusMultiplier -= $attacker->getWonderPerkMultiplier('enemy_casualties_defense');

            $multiplier *= $nonUnitBonusMultiplier;

            // Unit bonuses (multiplicative with non-unit bonuses)
            $unitBonusMultiplier = 0;

            // Unit Perk: Fewer Casualties (only on military units with a slot, draftees don't have this perk)
            if ($slot) {
                $unitBonusMultiplier -= ($dominion->race->getUnitPerkValueForUnitSlot($slot, ['casualties', 'casualties_defense']) / 100);
            }

            // Unit Perk: Reduce Combat Losses
            $unitsAtHomePerSlot = [];
            $unitsAtHomeRCLSlot = null;
            $reducedCombatLossesMultiplierAddition = 0;

            // todo: inefficient to do run this code per slot. needs refactoring
            foreach ($dominion->race->units as $unit) {
                $slot = $unit->slot;
                $unitKey = "military_unit{$slot}";

                $unitsAtHomePerSlot[$slot] = $dominion->{$unitKey};

                if ($unit->getPerkValue('reduce_combat_losses') !== 0) {
                    $unitsAtHomeRCLSlot = $slot;
                }
            }

            // We have a unit with RCL!
            if ($unitsAtHomeRCLSlot !== null) {
                $totalUnitsAtHome = array_sum($unitsAtHomePerSlot);
                $totalUnitsAtHome += $dominion->military_draftees;

                if ($totalUnitsAtHome > 0) {
                    $reducedCombatLossesMultiplierAddition += (($unitsAtHomePerSlot[$unitsAtHomeRCLSlot] / $totalUnitsAtHome) / 2);
                }
            }

            $unitBonusMultiplier += $reducedCombatLossesMultiplierAddition;

            // Apply to multiplier (multiplicative)
            $multiplier *= (1 - $unitBonusMultiplier);
        }

        return $multiplier;
    }

    /**
     * Returns the Dominion's total offensive non-unit casualty reduction.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOffensiveCasualtiesMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Spells
        $multiplier -= $dominion->getSpellPerkMultiplier('casualties');
        $multiplier -= $dominion->getSpellPerkMultiplier('casualties_offense');

        // Techs
        $multiplier -= $dominion->getTechPerkMultiplier('casualties');
        $multiplier -= $dominion->getTechPerkMultiplier('casualties_offense');

        // Wonders
        $multiplier -= $dominion->getWonderPerkMultiplier('casualties_offense');

        // Heroes
        $multiplier -= $this->heroCalculator->getHeroPerkMultiplier($dominion, 'casualties');

        // Cap at -80%
        return (1 - min(0.8, $multiplier));
    }

    /**
     * Returns the Dominion's total defensive non-unit casualty reduction.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getDefensiveCasualtiesMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Spells
        $multiplier -= $dominion->getSpellPerkMultiplier('casualties');
        $multiplier -= $dominion->getSpellPerkMultiplier('casualties_defense');

        // Techs
        $multiplier -= $dominion->getTechPerkMultiplier('casualties');
        $multiplier -= $dominion->getTechPerkMultiplier('casualties_defense');

        // Wonders
        $multiplier -= $dominion->getWonderPerkMultiplier('casualties_defense');

        // Heroes
        $multiplier -= $this->heroCalculator->getHeroPerkMultiplier($dominion, 'casualties');

        // Cap at -80%
        return (1 - min(0.8, $multiplier));
    }

    /**
     * Returns the Dominion's casualties by unit type.
     *
     * @param  Dominion $dominion
     * @param int $foodDeficit
     * @return array
     */
    public function getStarvationCasualtiesByUnitType(Dominion $dominion, int $foodDeficit): array
    {
        $units = $this->getStarvationUnitTypes();

        $totalCasualties = $this->getTotalStarvationCasualties($dominion, $foodDeficit);

        if ($totalCasualties === 0) {
            return [];
        }

        $peasantPopPercentage = $dominion->peasants / $this->populationCalculator->getPopulation($dominion);
        $totalMilitary = (
            $dominion->military_draftees +
            $dominion->military_unit1 +
            $dominion->military_unit2 +
            $dominion->military_unit3 +
            $dominion->military_unit4
        );

        $casualties = ['peasants' => (int)min($totalCasualties * $peasantPopPercentage, $dominion->peasants)];
        $casualties += array_fill_keys($units, 0);

        $remainingCasualties = ($totalCasualties - array_sum($casualties));
        $militaryCasualties = $remainingCasualties;

        foreach($units as $unit) {
            if($remainingCasualties == 0) {
                break;
            }

            $slotTotal = $dominion->{$unit};

            if($slotTotal == 0) {
                continue;
            }

            $slotLostMultiplier = $slotTotal / $totalMilitary;
            $slotLost = floor($militaryCasualties * $slotLostMultiplier);

            if($slotLost > $slotTotal) {
                $slotLost = $slotTotal;
            }

            $casualties[$unit] += $slotLost;
            $remainingCasualties -= $slotLost;
        }

        if ($remainingCasualties > 0) {
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
     * @param int $foodDeficit
     * @return int
     */
    public function getTotalStarvationCasualties(Dominion $dominion, int $foodDeficit): int
    {
        if ($foodDeficit >= 0) {
            return 0;
        }

        $casualties = (int)abs($foodDeficit);
        $maxCasualties = $this->populationCalculator->getPopulation($dominion) * 0.02;

        return min($casualties, $maxCasualties);
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
                $this->unitHelper->getUnitTypes(true)
            ),
            ['military_draftees']
        );
    }

    /**
     * @param Dominion $dominion
     * @param Dominion $target
     * @param int $slot
     * @return bool
     */
    protected function isImmortalVersusRacePerk(Dominion $dominion, Dominion $target, int $slot): bool
    {
        $raceNameFormatted = strtolower($target->race->name);
        $raceNameFormatted = str_replace(' ', '_', $raceNameFormatted);

        $perkValue = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'immortal_except_vs');

        if(!$perkValue)
        {
            return false;
        }

        return $perkValue !== $raceNameFormatted;
    }
}
