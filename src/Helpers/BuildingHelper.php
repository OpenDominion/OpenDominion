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

    public function getBuildingTypesByRace(Race $race = null)
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
            'smithy' => 2,
            'masonry' => 0, // increase castle bonuses
            'ore_mine' => 2,
            'gryphon_nest' => 2,
            'tower' => 2,
            'wizard_guild' => 0, // increase wizard strength
            'temple' => 1, // reduce defensive bonuses of target dominion during invasion
            'diamond_mine' => 2,
            'school' => 0, // produces research points
            'lumberyard' => 2,
            'forest_haven' => 1, // reduce losses on failed spy ops, reduce fireball damage, reduce plat stolemn
            'factory' => 2,
            'guard_tower' => 2,
            'shrine' => 0, // reduce casualties on offense, increases chance of hero level gain?, increase hero bonuses?
            'barracks' => 2,
            'dock' => 1, // prevents boats being sunk
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

        return null;
    }

    public function getBuildingHelpString($buildingType)
    {
        $helpStrings = [
            'home' => 'Houses 30 people.',
            'alchemy' => 'Produces 45 platinum per hour.',
            'farm' => 'Produces 80 bushels of food per hour.<br><br>Each person eats 0.25 of a bushel of food per hour.',
            'smithy' => 'Reduces specialist and elite military unit training costs.<br><br>Training cost reduced by 2% per 1% owned, up to a maximum of 36% at 18% owned.',
            'masonry' => 'Increases castle bonuses and reduces Lightning Bolt damage.<br><br>Bonuses increased by 2.75% per 1% owned.<br>Lightning Bolt damage reduced by 0.75% per 1% owned, up to a maximum of 25% at 33.3% owned.',
            'ore_mine' => 'Produces 60 ore per hour.',
            'gryphon_nest' => 'Increases offensive power.<br><br>Power increased by 1.75% per 1% owned, up to a maximum of 35% at 20% owned.',
            'tower' => 'Produces 25 mana per hour.',
            'wizard_guild' => 'Increases Wizard Strength refresh rate, reduces Wizard and ArchMages training cost and reduces spell costs.<br><br>Wizard Strength refresh rate increased by 0.1% per 1% owned, up to a maximum of 2% at 20% owned.<br>Wizard and ArchMage training and spell costs reduced by 2% per 1% owned, up to a maximum of 40% at 20% owned.',
            'temple' => 'Increases population growth and reduces defensive bonuses of dominions you invade.<br><br>Population growth increased by 6% per 1% owned.<br>Defensive bonuses reduced by 1.5% per 1% owned, up to a maximum of 25% at 16.7% owned.',
            'diamond_mine' => 'Produces 15 gems per hour.',
            'school' => 'Produces research points.',
            'lumberyard' => 'Produces 50 lumber per hour.',
            'forest_haven' => 'Increases peasant defense, reduces losses on failed spy ops, reduces incoming Fireball damage and reduces platinum theft.<br><br>Each Forest Haven gives 20 peasants 0.75 defense each.<br>Failed spy ops losses reduced by 3% per 1% owned, up to a maximum of 30% at 10% owned.<br>Fireball damage and platinum theft reduced by 8% per 1% owned, up to a maximum of 80% at 10% owned.',
            'factory' => 'Reduces construction and land rezoning costs.<br><br>Construction costs reduced by 4% per 1% owned, up to a maximum of 75% at 18.75% owned.<br>Rezoning costs reduced by 3% per 1% owned, up to a maximum of 75% at 25% owned.',
            'guard_tower' => 'Increases defensive power.<br><br>Power increased by 1.75% per 1% owned, up to a maximum of 35% at 20% owned.',
            'shrine' => 'Reduces offensive casualties.<br><br>Casualties reduced by 4% per 1% owned, up to a maximum of 80% at 20% owned.', // todo: hero level gain and hero bonuses
            'barracks' => 'Houses 36 trained or training military units.<br><br>Does not increase in capacity for population bonuses.',
            'dock' => 'Produces 1 boat every 20 hours on average, produces 35 bushels of food per hour and each dock prevents 2.5 your boats from being sunk.',
        ];

        return $helpStrings[$buildingType] ?: null;
    }
}
