<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Race;

class BuildingHelper
{
    public function getBuildingTypes()
    {
        return [
            'home',
            'alchemy',
            'farm',
            'smithy',
            'masonry',
            'ore_mine',
            'gryphon_nest',
            'tower',
            'wizard_guild',
            'temple',
            'diamond_mine',
            'school',
            'lumberyard',
            'forest_haven',
            'factory',
            'guard_tower',
            'shrine',
            'barracks',
            'dock',
        ];
    }

    public function getBuildingTypesByLandType(Race $race = null)
    {
        $return = [
            'plain' => [
                'alchemy',
                'farm',
                'smithy',
                'masonry',
            ],
            'mountain' => [
                'ore_mine',
                'gryphon_nest',
            ],
            'swamp' => [
                'tower',
                'wizard_guild',
                'temple',
            ],
            'cavern' => [
                'diamond_mine',
                'school',
            ],
            'forest' => [
                'lumberyard',
                'forest_haven',
            ],
            'hill' => [
                'factory',
                'guard_tower',
                'shrine',
                'barracks',
            ],
            'water' => [
                'dock',
            ],
        ];

        if ($race !== null) {
            array_unshift($return[$race->home_land_type], 'home');
        }

        return $return;
    }

    // temp
    public function getBuildingImplementedString($buildingType)
    {
        // 0 = nyi
        // 1 = partial implemented
        // 2 = implemented

        $buildingTypes = [
            'home' => 2,
            'alchemy' => 2,
            'farm' => 2,
            'smithy' => 0, // reduce military unit cost
            'masonry' => 0, // increase castle bonuses
            'ore_mine' => 0, // produces ore
            'gryphon_nest' => 2,
            'tower' => 0, // produces mana
            'wizard_guild' => 0, // increase wizard strength
            'temple' => 0, // increase population growth, reduce defensive bonuses of target dominion during invasion
            'diamond_mine' => 0, // produces diamonds
            'school' => 0, // produces research points
            'lumberyard' => 2,
            'forest_haven' => 1, // reduce losses on failed spy ops, reduce fireball damage, reduce plat stolemn
            'factory' => 0, // reduce construction costs and rezoning costs
            'guard_tower' => 2,
            'shrine' => 0, // reduce casualties on offense, increases chance of hero level gain?, increase hero bonuses?
            'barracks' => 2,
            'dock' => 1, // produces boats, prevents boats being sunk
        ];

        switch ($buildingTypes[$buildingType]) {
            case 0:
                return '<abbr title="Not yet implemented" class="label label-danger">NYI</abbr>';
                break;

            case 1:
                return '<abbr title="Partially implemented" class="label label-warning">PI</abbr>';
                break;

//            case 2:
//                break;
        }
    }
}
