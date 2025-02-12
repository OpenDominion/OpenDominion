<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Race;
use OpenDominion\Models\Unit;

class UnitHelper
{
    public function getUnitTypes(bool $hideSpecialUnits = false): array
    {
        $data = [
            'unit1',
            'unit2',
            'unit3',
            'unit4',
        ];

        if (!$hideSpecialUnits) {
            $data = array_merge($data, [
                'spies',
                'assassins',
                'wizards',
                'archmages',
            ]);
        }

        return $data;
    }

    public function getUnitName(string $unitType, Race $race): string
    {
        if (in_array($unitType, ['spies', 'assassins', 'wizards', 'archmages'], true)) {
            return ucfirst($unitType);
        }

        $unitSlot = (((int)str_replace('unit', '', $unitType)) - 1);

        return $race->units[$unitSlot]->name;
    }

    public function getUnitCostStringFromArray(array $unitCosts): string
    {
        $labelParts = [];

        foreach ($unitCosts as $costType => $value) {
            switch ($costType) {
                case 'draftees':
                    break;

                case 'spies':
                    $labelParts[] = "{$value} spy";
                    break;

                case 'wizards':
                    $labelParts[] = "{$value} wizard";
                    break;

                default:
                    $labelParts[] = "{$value} {$costType}";
                    break;
            }
        }

        return implode(', ', $labelParts);
    }

    public function getUnitCostString(Unit $unit): string
    {
        $unitCosts = [];

        if ($unit->cost_platinum) {
            $unitCosts['platinum'] = $unit->cost_platinum;
        }

        if ($unit->cost_ore > 0) {
            $unitCosts['ore'] = $unit->cost_ore;
        }

        if ($unit->cost_mana > 0) {
            $unitCosts['mana'] = $unit->cost_mana;
        }

        if ($unit->cost_lumber > 0) {
            $unitCosts['lumber'] = $unit->cost_lumber;
        }

        if ($unit->cost_gems > 0) {
            $unitCosts['gems'] = $unit->cost_gems;
        }

        return $this->getUnitCostStringFromArray($unitCosts);
    }

    public function getUnitHelpString(string $unitType, Race $race, bool $withOpDp = false): ?string
    {
        if ($unitType == 'draftees') {
            $drafteeHelpString = 'Basic military unit.';

            if ($withOpDp) {
                $drafteeHelpString .= '<br>Offensive Power: 0<br>Defensive Power: 1';
            }

            return $drafteeHelpString . '<br><br>Used for exploring and training other units.';
        }

        $helpStrings = [
            'unit1' => 'Offensive specialist',
            'unit2' => 'Defensive specialist',
            'unit3' => 'Defensive elite',
            'unit4' => 'Offensive elite',
            'spies' => 'Used for espionage.',
            'assassins' => 'Used for espionage.<br><br>Twice as strong as spies and cannot be disbanded.<br>50% of losses are retrained as spies.',
            'wizards' => 'Used for casting offensive spells.',
            'archmages' => 'Used for casting offensive spells.<br><br>Counts as two wizards and cannot be assassinated.<br>Black ops losses reduced by 90%.',
        ];

        // todo: refactor this. very inefficient
        $perkTypeStrings = [
            // Conversions
            'conversion' => 'Converts enemy peasants into %1$s (up to 1 for every %2$g sent on attack).',
            'staggered_conversion' => 'Converts some enemy casualties into %2$s against dominions %1$g%%+ of your size.',
            'upgrade_casualties' => 'Casualties are converted into %1$s (1 for every %2$g killed).',
            'upgrade_survivors' => '%2$g%% of survivors return from battle as %1$s against dominions 75%%+ of your size.',

            // OP/DP related
            'defense_from_building' => 'Defense increased by 1 for every %2$g%% %1$ss (max +%3$g).',
            'offense_from_building' => 'Offense increased by 1 for every %2$g%% %1$ss (max +%3$g).',

            'defense_from_land' => 'Defense increased by 1 for every %2$g%% %1$ss (max +%3$g).',
            'offense_from_land' => 'Offense increased by 1 for every %2$g%% %1$ss (max +%3$g).',

            'defense_from_pairing' => 'Defense increased by %3$g when paired with %2$g %1$s at home.',
            'offense_from_pairing' => 'Offense increased by %3$g when paired with %2$g %1$s on attack.',

            'defense_from_prestige' => 'Defense increased by 1 for every %1$g prestige (max +%2$g).',
            'offense_from_prestige' => 'Offense increased by 1 for every %1$g prestige (max +%2$g).',

            'defense_vs_building' => 'Defense decreased by 1 for every %2$g%% %1$ss of defender (max %3$g).',
            'offense_vs_building' => 'Offense decreased by 1 for every %2$g%% %1$ss of defender (max %3$g).',

            'defense_vs_goblin' => 'Defense increased by %g against goblins.',
            'offense_vs_goblin' => 'Offense increased by %g against goblins.',
            'defense_vs_kobold' => 'Defense increased by %g against kobolds.',
            'offense_vs_kobold' => 'Offense increased by %g against kobolds.',
            'defense_vs_wood_elf' => 'Defense increased by %g against wood elves.',
            'offense_vs_wood_elf' => 'Offense increased by %g against wood elves.',

            'offense_from_spell' => 'Offense increased by %2$g while %1$s is active.',
            'offense_staggered_land_range' => 'Offense increased by %2$g against dominions %1$g%%+ of your size.',

            'offense_raw_wizard_ratio' => 'Offense increased by %1$g * Raw Wizard Ratio (max +%2$g).',

            // Spy related
            'counts_as_spy_defense' => 'Each unit counts as %.2f of a spy on defense.',
            'counts_as_spy_offense' => 'Each unit counts as %.2f of a spy on offense.',

            // Wizard related
            'counts_as_wizard_defense' => 'Each unit counts as %.2f of a wizard on defense.',
            'counts_as_wizard_offense' => 'Each unit counts as %.2f of a wizard on offense.',

            // Casualties related
            'casualties' => '%+d%% casualties.',
            'casualties_defense' => '%+d%% defensive casualties.',
            'casualties_offense' => '%+d%% offensive casualties.',
            'casualties_offense_range' => '%+d%% offensive casualties against dominions 75%%+ of your size.',
            'fixed_casualties' => 'ALWAYS suffers %g%% casualties.',

            'immortal' => 'Immortal.',
            'immortal_except_vs' => 'Immortal, except vs %s.',
            'immortal_from_pairing' => 'Immortal on attack when paired with %2$g %1$s at home.',
            'immortal_vs_land_range' => 'Immortal when attacking dominions %g%%+ of your size.',

            'kills_immortal' => 'Can kill all immortal units.',
            'reduce_combat_losses' => 'Reduces combat losses.',
            'rebirth' => 'Reborn %g hours after death.',

            // Resource related
            'lumber_from_land' => 'Each unit produces 1 lumber per hour for every %2$g%% %1$ss (max +%3$g).',
            'ore_production' => 'Each unit produces %g units of ore per hour.',
            'plunder_platinum' => 'Each unit plunders %g platinum on attack (max 1 hour of target\'s raw production).',
            'plunder_gems' => 'Each unit plunders %g gems on attack (max 1 hour of target\'s raw production).',
            'plunder_mana' => 'Each unit plunders %g mana on attack (max 1 hour of target\'s raw production).',
            'salvage_lumber' => 'Each unit salvages %g lumber from the battlefield on attack.',
            'salvage_ore' => 'Each unit salvages %g ore from the battlefield on attack.',
            'sink_boats_defense' => 'Sinks boats when defending.',
            'sink_boats_offense' => 'Sinks boats when attacking.',

            // Misc
            'faster_return' => 'Returns %g hours faster from battle.',
            'flavor_basher' => 'BASH!',
            'flavor_smasher' => 'SMASH!',
            'flavor_tunneler' => 'You no take candle!',
            'unit_housing' => 'Provides housing for %2$g %1$s (trained or in training).'
        ];

        $unitHelpString = $helpStrings[$unitType];
        $unitPowerHelpString = '';
        $unitPerkHelpString = '';
        // Get unit - same logic as military page
        if (in_array($unitType, ['unit1', 'unit2', 'unit3', 'unit4'])) {
            $unit = $race->units->filter(function ($unit) use ($unitType) {
                return ($unit->slot == (int)str_replace('unit', '', $unitType));
            })->first();

            list($type, $proficiency) = explode(' ', $unitHelpString);

            if ($unit->type) {
                list($type, $proficiency) = explode('_', $unit->type);
                $type = ucfirst($type);
            }

            $proficiency .= '.';
            $unitHelpString = "$type $proficiency";
            $hasOffensivePowerPerk = false;
            $hasDefensivePowerPerk = false;

            foreach ($unit->perks as $perk) {

                if (!$hasOffensivePowerPerk) {
                    $hasOffensivePowerPerk = strpos($perk->key, 'offense_from') !== false;
                }
                if (!$hasDefensivePowerPerk) {
                    $hasDefensivePowerPerk = strpos($perk->key, 'defense_from') !== false;
                }

                if (!array_key_exists($perk->key, $perkTypeStrings)) {
                    //\Debugbar::warning("Missing perk help text for unit perk '{$perk->key}'' on unit '{$unit->name}''.");
                    continue;
                }

                $perkValue = $perk->pivot->value;

                // Handle array-based perks
                $nestedArrays = false;
                // todo: refactor all of this
                // partially copied from Race::getUnitPerkValueForUnitSlot
                if (str_contains($perkValue, ',') || str_contains($perkValue, ';')) {
                    $perkValue = explode(',', $perkValue);

                    foreach ($perkValue as $key => $value) {
                        if (!str_contains($value, ';')) {
                            continue;
                        }

                        $nestedArrays = true;
                        $perkValue[$key] = explode(';', $value);
                    }
                }

                // Special case for pairings
                if ($perk->key === 'defense_from_pairing' || $perk->key === 'offense_from_pairing' || $perk->key === 'immortal_from_pairing') {
                    $slot = (int)$perkValue[0];
                    $pairedUnit = $race->units->filter(static function ($unit) use ($slot) {
                        return ($unit->slot === $slot);
                    })->first();

                    $perkValue[0] = $pairedUnit->name;
                    if ($perkValue[1] > 1) {
                        $perkValue[0] = str_plural($perkValue[0]);
                    }
                }

                // Special case for conversions
                if ($perk->key === 'conversion' || $perk->key === 'upgrade_casualties' || $perk->key === 'upgrade_survivors' || $perk->key === 'unit_housing') {
                    $slot = (int)$perkValue[0];
                    $amount = (int)$perkValue[1];

                    $unitToConvertTo = $race->units->filter(static function ($unit) use ($slot) {
                        return ($unit->slot === $slot);
                    })->first();

                    $perkValue[0] = $unitToConvertTo->name;
                    $perkValue[1] = $amount;
                    if ($perkValue[1] > 1) {
                        $perkValue[0] = str_plural($perkValue[0]);
                    }
                }

                // Special case for spells
                if ($perk->key === 'offense_from_spell') {
                    $spellKey = $perkValue[0];
                    $amount = (int)$perkValue[1];

                    $spellHelper = app(SpellHelper::class);
                    $spell = $spellHelper->getSpellByKey($spellKey);

                    $perkValue[0] = $spell->name;
                    $perkValue[1] = $amount;
                }

                if (is_array($perkValue)) {
                    if ($nestedArrays) {
                        foreach ($perkValue as $nestedKey => $nestedValue) {
                            $unitPerkHelpString .= ('<br><br>' . vsprintf($perkTypeStrings[$perk->key], $nestedValue));
                        }
                    } else {
                        $unitPerkHelpString .= ('<br><br>' . vsprintf($perkTypeStrings[$perk->key], $perkValue));
                    }
                } else {
                    $unitPerkHelpString .= ('<br><br>' . sprintf($perkTypeStrings[$perk->key], $perkValue));
                }
            }

            if ($unit->need_boat === false) {
                $unitPerkHelpString .= ('<br><br>No boats needed.');
            }

            if ($withOpDp) {
                $offensivePower = $unit->power_offense;
                $defensivePower = $unit->power_defense;
                $unitPowerHelpString .= "<br>Offensive Power: $offensivePower";
                if ($hasOffensivePowerPerk) {
                    $unitPowerHelpString .= '*';
                }
                $unitPowerHelpString .= "<br>Defensive Power: $defensivePower";
                if ($hasDefensivePowerPerk) {
                    $unitPowerHelpString .= '*';
                }
            }
        }

        $unitHelpString .= $unitPowerHelpString;
        $unitHelpString .= $unitPerkHelpString;

        return $unitHelpString ?: null;
    }

    public function getUnitTypeIconHtml(string $unitType, Race $race = null): string
    {
        switch ($unitType) {
            case 'draftees':
                $iconClass = 'ra ra-player';
                $colorClass = 'text-green';
                break;

            case 'unit1':
                $iconClass = 'ra ra-sword';
                $colorClass = 'text-green';
                break;

            case 'unit2':
                $iconClass = 'ra ra-shield';
                $colorClass = 'text-green';
                break;

            case 'unit3':
                $iconClass = 'ra ra-shield';
                $colorClass = 'text-light-blue';
                break;

            case 'unit4':
                $iconClass = 'ra ra-sword';
                $colorClass = 'text-light-blue';
                break;

            case 'spies':
                $iconClass = 'ra ra-plain-dagger';
                $colorClass = 'text-green';
                break;

            case 'assassins':
                $iconClass = 'ra ra-dripping-blade';
                $colorClass = 'text-light-blue';
                break;

            case 'wizards':
                $iconClass = 'ra ra-fairy-wand';
                $colorClass = 'text-green';
                break;

            case 'archmages':
                $iconClass = 'ra ra-crystal-wand';
                $colorClass = 'text-light-blue';
                break;

            default:
                return '';
        }

        if ($race && in_array($unitType, ['unit1', 'unit2', 'unit3', 'unit4'])) {
            $unit = $race->units->filter(function ($unit) use ($unitType) {
                return ($unit->slot == (int)str_replace('unit', '', $unitType));
            })->first();
            if ($unit->type) {
                list($type, $proficiency) = explode('_', $unit->type);
                if (strtolower($type) == 'offensive') {
                    $iconClass = 'ra ra-sword';
                } elseif (strtolower($type) == 'defensive') {
                    $iconClass = 'ra ra-shield';
                } elseif (strtolower($type) == 'hybrid') {
                    $iconClass = 'ra ra-crossed-swords';
                }
                if (strtolower($proficiency) == 'specialist') {
                    $colorClass = 'text-green';
                } elseif (strtolower($proficiency) == 'elite') {
                    $colorClass = 'text-light-blue';
                }
            }
        }

        return "<i class=\"$iconClass $colorClass\"></i>";
    }

    public function getConvertedUnitsString(array $convertedUnits, Race $race): string
    {
        $result = 'In addition, your army converts ';
        $convertedUnitsFiltered = array_filter($convertedUnits, function ($item) {
            return $item > 0;
        });

        $numberOfUnitTypesConverted = count($convertedUnitsFiltered);
        $i = 1;

        // todo: this can probably be refactored to use generate_sentence_from_array() in helpers.php
        foreach ($convertedUnitsFiltered as $slotNumber => $amount) {
            if ($i !== 1) {
                if ($numberOfUnitTypesConverted === $i) {
                    $result .= ' and ';
                } else {
                    $result .= ', ';
                }
            }

            $formattedAmount = number_format($amount);

            $result .= "{$formattedAmount} {$race->units[$slotNumber - 1]->name}s";

            $i++;
        }

        $result .= '!';

        return $result;
    }
}
