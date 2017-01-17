<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Race;

class LandHelper
{
    public function getLandTypes()
    {
        return [
            'plain',
            'mountain',
            'swamp',
            'cavern',
            'forest',
            'hill',
            'water',
        ];
    }

    public function getLandTypeForBuildingByRace($building, Race $race)
    {
        return []; // todo
    }

    public function getLandTypesByBuildingType(Race $race)
    {
        $return = [
            'alchemy' => 'plain',
            'farm' => 'plain',
//            'smithy' => 'plain',
//            'masonry' => 'plain',
//            'ore_mine' => 'mountain',
//            'gryphon_nest' => 'mountain',
//            'tower' => 'swamp',
//            'wizard_guild' => 'swamp',
//            'temple' => 'swamp',
//            'diamond_mine' => 'cavern',
//            'school' => 'cavern',
            'lumberyard' => 'forest',
//            'forest_haven' => 'forest',
//            'factory' => 'hill',
//            'guard_tower' => 'hill',
//            'shrine' => 'hill',
            'barracks' => 'hill',
//            'dock' => 'water',
        ];

        $return = (['home' => $race->home_land_type] + $return);

        return $return;
    }
}
