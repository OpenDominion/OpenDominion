<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Race;

class BuildingHelper
{
    public function getBuildingTypes()
    {
        return [
            'home' => 'Home',
            'alchemy' => 'Alchemy',
            'farm' => 'Farm',
//            'smithy' => 'Smithy',
//            'masonry' => 'Masonry',
//            'ore_mine' => 'Ore Mine',
//            'gryphon_nest' => 'Gryphon Nest',
//            'tower' => 'Tower',
//            'wizard_guild' => 'Wizard Guild',
//            'temple' => 'Temple',
//            'diamond_mine' => 'Diamond Mine',
//            'school' => 'School',
            'lumberyard' => 'Lumberyard',
//            'forest_haven' => 'Forest Haven',
//            'factory' => 'Factory',
//            'guard_tower' => 'Guard Tower',
//            'shrine' => 'Shrine',
            'barracks' => 'Barracks',
//            'dock' => 'Dock',
        ];
    }

    public function getBuildingTypesByLandType(Race $race = null)
    {
        $return = [
            'plain' => [
                'alchemy',
                'farm',
//                'smithy',
//                'masonry',
            ],
            'mountain' => [
//                'ore_mine',
//                'gryphon_nest',
            ],
            'swarmp' => [
//                'tower',
//                'wizard_guild',
//                'template',
            ],
            'cavern' => [
//                'diamond_mine',
//                'school',
            ],
            'forest' => [
                'lumberyard',
//                'forest_haven',
            ],
            'hill' => [
//                'factory',
//                'guard_tower',
//                'shrine',
                'barracks',
            ],
            'water' => [
//                'dock',
            ],
        ];

        if ($race !== null) {
            array_unshift($return[$race->home_land_type], 'home');
        }

        return $return;
    }
}
