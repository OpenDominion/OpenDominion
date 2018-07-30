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
            'reduce_combat_losses' => 'Reduces combat losses.',
        ];

        foreach ($race->units as $unit) {
            $perkType = $unit->perkType;

            if ($perkType === null) {
                continue;
            }

            $helpStrings['unit' . $unit->slot] .= ('<br><br>' . sprintf($perkTypeStrings[$perkType->key], $unit->unit_perk_type_values));
        }

        return $helpStrings[$unitType] ?: null;
    }
}
