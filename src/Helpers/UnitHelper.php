<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Race;

class UnitHelper
{
    public function getUnitTypes(): array
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
            /* added hardcoded values for Human/Nomad, will update with php code per race */
            'unit1' => 'Offensive specialist. 3/0',
            'unit2' => 'Defensive specialist. 0/3',
            'unit3' => 'Defensive elite. 0/6',
            'unit4' => 'Offensive elite. 6/2',
            'spies' => 'Used for espionage.',
            'wizards' => 'Used for casting offensive spells.',
            'archmages' => 'Used for casting offensive spells.<br><br>Immortal and twice as strong as regular wizards.',
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

            $helpStrings['unit' . $unit->slot] .= ('<br><br>' . sprintf($perkTypeStrings[$perkType->key], $unit->unit_perk_type_values));
        }

        return $helpStrings[$unitType] ?: null;
    }
}
