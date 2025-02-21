<?php

namespace OpenDominion\Helpers;

use Illuminate\Support\Collection;

class AchievementHelper
{
    /*
    What else do we need for this to work?
    Do we need a unique key to check against? probably
    AchievementService
     - iterate over all dominions
       - compare unlocks to this list
       - iterate over locked achievements
         - check if stat >= value
     - option to create these in the DB if they don't exist
     - method to unlock for X user
    Hook into tickDaily?
    (requires rounds->activeRankings for final 'tick')
    or run ONLY at end of round?

    When displaying in valhalla, can we ignore lesser version of the same one?
    Use category or make names identical + unique key?

    Skip one-time unlocks like test rounds and the like
    (via category? or just not defined at all)
    */

    public function getAchievements()
    {
        return collect([
            [
                'name' => '',
                'description' => 'Obtain 1000 acres of land',
                'category' => 'general',
                'icon' => '',
                'stat' => 'highest_land_achieved',
                'value' => 1000
            ],
            [
                'name' => '',
                'description' => 'Obtain 2000 acres of land',
                'category' => 'general',
                'icon' => ''
            ],
            [
                'name' => '',
                'description' => '',
                'category' => '',
                'icon' => ''
            ],
            [
                'name' => '',
                'description' => '',
                'category' => '',
                'icon' => ''
            ],
            [
                'name' => '',
                'description' => '',
                'category' => '',
                'icon' => ''
            ],
            [
                'name' => '',
                'description' => '',
                'category' => '',
                'icon' => ''
            ],
            [
                'name' => '',
                'description' => '',
                'category' => '',
                'icon' => ''
            ]
        ]);
    }
}
