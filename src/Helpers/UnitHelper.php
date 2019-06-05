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
            'immortal_except_vs_icekin' => 'Immortal (except vs Icekin).',
            'fewer_casualties_offense' => '%s%% fewer casualties on offense.',
            'counts_as_spy' => 'Each unit count as %s of a spy.',
        ];

        foreach ($race->units as $unit) {
            foreach ($unit->perks->whereIn('key', array_keys($perkTypeStrings)) as $perk) {
                $helpStrings['unit' . $unit->slot] .= ('<br><br>' . sprintf($perkTypeStrings[$perk->key], $perk->pivot->value));
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
