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
}
