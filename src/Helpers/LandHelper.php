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
}
