<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;

class CasualtiesCalculator
{
    /** @var LandCalculator */
    protected $landCalculator;

    /** @var PopulationCalculator */
    private $populationCalculator;

    /** @var SpellCalculator */
    private $spellCalculator;

    /** @var UnitHelper */
    protected $unitHelper;

    /**
     * CasualtiesCalculator constructor.
     *
     * @param LandCalculator $landCalculator
     * @param PopulationCalculator $populationCalculator
     * @param SpellCalculator $spellCalculator
     * @param UnitHelper $unitHelper
     */
    public function __construct(LandCalculator $landCalculator, PopulationCalculator $populationCalculator, SpellCalculator $spellCalculator, UnitHelper $unitHelper)
    {
        $this->landCalculator = $landCalculator;
        $this->spellCalculator = $spellCalculator;
        $this->unitHelper = $unitHelper;
        $this->populationCalculator = $populationCalculator;
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
     * @param bool $isOverwhelmed
     * @return float
     */
    public function getOffensiveCasualtiesMultiplierForUnitSlot(Dominion $dominion, Dominion $target, int $slot, array $units, float $landRatio, bool $isOverwhelmed): float
    {
        $multiplier = 1;

        // Check if unit has fixed casualties first, so we can skip all other checks
        if ($dominion->race->getUnitPerkValueForUnitSlot($slot, 'fixed_casualties') !== 0) {
            return 1;
        }

        // Then check immortality, so we can skip the other remaining checks if we indeed have immortal units, since
        // casualties will then always be 0 anyway

        // Immortality never triggers upon being overwhelmed
        if (!$isOverwhelmed) {
            // Global immortality
            if ((bool)$dominion->race->getUnitPerkValueForUnitSlot($slot, 'immortal')) {
                // Contrary to Dominion Classic, invading SPUDs are always immortal in OD, even when invading a HuNo
                // with Crusade active

                // This is to help the smaller OD player base (compared to DC) by not excluding HuNo as potential
                // invasion targets

                $multiplier = 0;
            }

            // Range-based immortality
            if (($multiplier !== 0) && (($immortalVsLandRange = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'immortal_vs_land_range')) !== 0)) {
                if ($landRatio >= ($immortalVsLandRange / 100)) {
                    $multiplier = 0;
                }
            }

            // Race perk-based immortality
            if (($multiplier !== 0) && $this->isImmortalVersusRacePerk($dominion, $target->race->name, $slot)) {
                $multiplier = 0;
            }
        }

        if ($multiplier !== 0) {
            // Non-unit bonuses (hero, shrines, tech, wonders), capped at -80%
            // Values (percentages)
            $spellRegeneration = 25;

            $nonUnitBonusMultiplier = 0;

            // todo: Heroes

            // Shrines
            $nonUnitBonusMultiplier += $this->getOffensiveCasualtiesReductionFromShrines($dominion);

            // Spells
            $nonUnitBonusMultiplier += $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, 'regeneration', $spellRegeneration);

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

                if ($unit->getPerkValue('reduce_combat_losses') !== 0) {
                    // todo: hacky workaround for not allowing RCL for gobbos (feedback from Gabbe)
                    //  Needs to be refactored later; unit perk should be renamed in the yml to reduce_combat_losses_defense
                    if ($dominion->race->name === 'Goblin') {
                        continue;
                    }

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
     * @param Dominion $attacker
     * @param int|null $slot Null is for non-racial units and thus used as draftees casualties multiplier
     * @return float
     */
    public function getDefensiveCasualtiesMultiplierForUnitSlot(Dominion $dominion, Dominion $attacker, ?int $slot): float
    {
        $multiplier = 1;

        // First check immortality, so we can skip the other remaining checks if we indeed have immortal units, since
        // casualties will then always be 0 anyway

        // Only military units with a slot number could be immortal
        if ($slot !== null) {
            // Global immortality
            if ((bool)$dominion->race->getUnitPerkValueForUnitSlot($slot, 'immortal')) {
                // Note: At the moment only SPUDs have the global 'immortal' perk. If we ever add global immortality to
                // other units later, we need to add checks in here so Crusade only works vs SPUD. And possibly
                // additional race-based checks in here for any new units. So always assume we're running SPUD at the
                // moment

                $attackerHasCrusadeActive = ($this->spellCalculator->getActiveSpellMultiplierBonus($attacker, 'crusade') !== 0);

                // Note: This doesn't do a race check on $attacker, since I don't think that's needed atm; only HuNo can
                // cast Crusade anyway. If we we add more races with Crusade or Crusade-like spells later, it should
                // go here

                // We're only immortal if they're not Deus-Vult'ing into our unholy lands :^)
                if (!$attackerHasCrusadeActive) {
                    $multiplier = 0;
                }
            }

            // Race perk-based immortality
            if (($multiplier !== 0) && $this->isImmortalVersusRacePerk($dominion, $attacker->race->name, $slot)) {
                $multiplier = 0;
            }
        }

        if ($multiplier !== 0) {
            // Non-unit bonuses (hero, tech, wonders), capped at -80%

            // Values (percentages)
            $spellRegeneration = 25;

            $nonUnitBonusMultiplier = 0;

            // todo: Heroes

            // todo: Tech

            // todo: Wonders

            // Spells
            $nonUnitBonusMultiplier += $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, 'regeneration', $spellRegeneration);

            // Cap at -80% and apply to multiplier (additive)
            $multiplier -= min(0.8, $nonUnitBonusMultiplier);

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

                if ($unit->getPerkValue('reduce_combat_losses') !== 0) {
                    $unitsAtHomeRCLSlot = $slot;
                }
            }

            // We have a unit with RCL!
            if ($unitsAtHomeRCLSlot !== null) {
                $totalUnitsAtHome = array_sum($unitsAtHomePerSlot);

                if ($totalUnitsAtHome > 0) {
                    $reducedCombatLossesMultiplierAddition += (($unitsAtHomePerSlot[$unitsAtHomeRCLSlot] / $totalUnitsAtHome) / 2);
                }
            }

            $unitBonusMultiplier += $reducedCombatLossesMultiplierAddition;

            // todo: Troll/Orc unit perks, possibly other perks elsewhere too

            // Apply to multiplier (multiplicative)
            $multiplier *= (1 - $unitBonusMultiplier);
        }

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
        $casualties = ['peasants' => min($totalCasualties * $peasantPopPercentage, $dominion->peasants)];
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
     * @param int $foodDeficit
     * @return int
     */
    public function getTotalStarvationCasualties(Dominion $dominion, int $foodDeficit): int
    {
        if ($foodDeficit >= 0) {
            return 0;
        }

        $casualties = (int)(abs($foodDeficit) * 2);
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
                $this->unitHelper->getUnitTypes()
            ),
            ['military_draftees']
        );
    }

    /**
     * @param Dominion $dominion
     * @param string $opposingForceRaceName
     * @param int $slot
     * @return bool
     */
    protected function isImmortalVersusRacePerk(Dominion $dominion, string $opposingForceRaceName, int $slot): bool
    {
        $raceNameFormatted = strtolower($opposingForceRaceName);
        $raceNameFormatted = str_replace(' ', '_', $raceNameFormatted);

        $perkValue = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'immortal_except_vs');

        if(!$perkValue)
        {
            return false;
        }

        return $perkValue !== $raceNameFormatted;
    }
}
