<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Race;

class UnitHelper
{
    public function getUnitTypes()
    {
        return [
            'unit1',
            'unit2',
            'unit3',
            'unit4',
            'spies',
            'wizards',
            'archmages',
        ];
    }

    public function getUnitName($unitType, Race $race)
    {
        if (in_array($unitType, ['spies', 'wizards', 'archmages'], true)) {
            return ucfirst($unitType);
        }

        $unitSlot = (((int)str_replace('unit', '', $unitType)) - 1);

        return $race->units[$unitSlot]->name;
    }

    public function getUnitHelpString($unitType, Race $race)
    {
        $helpStrings = [
            'unit1' => 'Offensive specialist.',
            'unit2' => 'Defensive specialist.',
            'unit3' => 'Defensive elite.',
            'unit4' => 'Offensive elite.',
            'spies' => 'Used for espionage.',
            'wizards' => 'Used for casting offensive spells.',
            'archmages' => 'Used for casting offensive spells. Twice as strong as regular wizards.',
        ];

        // todo: refactor this. very inefficient
        $perkTypeStrings = [
            'fewer_casualties' => '%s%% fewer casualties.',
            'faster_return' => 'Returns %s hours faster from battle.',
        ];

        foreach ($race->units as $unit) {
            $perkType = $unit->perkType;

            if ($perkType === null) {
                continue;
            }

            $helpStrings['unit' . $unit->slot] .= (' ' . sprintf($perkTypeStrings[$perkType->key], $unit->unit_perk_type_values));
        }

        return $helpStrings[$unitType] ?: null;
    }
}
