<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Race;

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
                'wizards',
                'archmages',
            ]);
        }

        return $data;
    }

    public function getUnitName(string $unitType, Race $race): string
    {
        if (in_array($unitType, ['spies', 'wizards', 'archmages'], true)) {
            return ucfirst($unitType);
        }

        $unitSlot = (((int)str_replace('unit', '', $unitType)) - 1);

        return $race->units[$unitSlot]->name;
    }

    public function getUnitHelpString(string $unitType, Race $race): ?string
    {
        $helpStrings = [
            'draftees' => 'Basic military unit.<br><br>Used for exploring and training other units.',
            'unit1' => 'Offensive specialist.',
            'unit2' => 'Defensive specialist.',
            'unit3' => 'Defensive elite.',
            'unit4' => 'Offensive elite.',
            'spies' => 'Used for espionage.',
            'wizards' => 'Used for casting offensive spells.',
            'archmages' => 'Used for casting offensive spells.<br><br>Immortal and twice as strong as regular wizards.',
        ];

        // todo: refactor this. very inefficient
        $perkTypeStrings = [
            'fewer_casualties' => '%s%% fewer casualties.',
            'faster_return' => 'Returns %s hours faster from battle.',
            'ore_production' => 'Each unit produces %s units of ore per hour.',
            'plunders_resources_on_attack' => 'Plunders resources on attack.',
            'reduce_combat_losses' => 'Reduces combat losses.',
            'immortal_except_vs' => 'Immortal (except vs %s).',
            'fewer_casualties_offense' => '%s%% fewer casualties on offense.',
            'counts_as_wizard_offense' => 'Each unit counts as %s of a wizard on offense.',
            'counts_as_wizard_defense' => 'Each unit counts as %s of a wizard on defense.',
            'immortal_vs_land_range' => 'Immortal when attacking dominions %s%% of your size.',
            'counts_as_spy_offense' => 'Each unit counts as %s of a spy on offense.',
            'counts_as_spy_defense' => 'Each unit counts as %s of a spy on defense.',
            'fixed_casualties' => 'ALWAYS suffers %s%% casualties.',
            'offense_from_building' => 'Offense increased by 1 for every %2$s%% %1$ss (max +%3$s).',
            'defense_from_building' => 'Defense increased by 1 for every %2$s%% %1$ss (max +%3$s).',
            'offense_from_land' => 'Offense increased by 1 for every %2$s%% %1$ss (max +%3$s).',
            'defense_from_land' => 'Defense increased by 1 for every %2$s%% %1$ss (max +%3$s).',
            'offense_raw_wizard_ratio' => 'Offense increased by %1$s * Raw Wizard Ratio (max +%2$s).',
            'offense_staggered_land_range' => 'Offense increased by %2$s against dominions %1$s%% of your size.',
            'offense_vs_goblin' => 'Offense increased by %s against goblins.',
            'defense_vs_goblin' => 'Defense increased by %s against goblins.',
        ];

        foreach ($race->units as $unit) {
            foreach ($unit->perks->whereIn('key', array_keys($perkTypeStrings)) as $perk) {
                $perkValue = $perk->pivot->value;

                // Handle array-based perks
                $nestedArrays = false;
                // todo: refactor all of this
                // partially copied from Race::getUnitPerkValueForUnitSlot
                if (str_contains($perkValue, ',')) {
                    $perkValue = explode(',', $perkValue);

                    foreach ($perkValue as $key => $value) {
                        if (!str_contains($value, ';')) {
                            continue;
                        }

                        $nestedArrays = true;
                        $perkValue[$key] = explode(';', $value);
                    }
                }

                if (is_array($perkValue)) {
                    if ($nestedArrays) {
                        foreach ($perkValue as $nestedKey => $nestedValue) {
                            $helpStrings['unit' . $unit->slot] .= ('<br><br>' . vsprintf($perkTypeStrings[$perk->key], $nestedValue));
                        }
                    } else {
                        $helpStrings['unit' . $unit->slot] .= ('<br><br>' . vsprintf($perkTypeStrings[$perk->key], $perkValue));
                    }
                } else {
                    $helpStrings['unit' . $unit->slot] .= ('<br><br>' . sprintf($perkTypeStrings[$perk->key], $perkValue));
                }
            }
        }

        return $helpStrings[$unitType] ?: null;
    }

    public function getUnitTypeIconHtml(string $unitType): string
    {
        switch ($unitType) {
            case 'draftees':
                return '<i class="fa fa-user text-green"></i>';

            case 'unit1':
                return '<i class="ra ra-sword text-green"></i>';

            case 'unit2':
                return '<i class="ra ra-shield text-green"></i>';

            case 'unit3':
                return '<i class="ra ra-shield text-light-blue"></i>';

            case 'unit4':
                return '<i class="ra ra-sword text-light-blue"></i>';

            case 'spies':
                return '<i class="fa fa-user-secret text-green"></i>';

            case 'wizards':
                return '<i class="ra ra-fairy-wand text-green"></i>';

            case 'archmages':
                return '<i class="ra ra-fairy-wand text-light-blue"></i>';

            default:
                return '';
        }
    }
    }
