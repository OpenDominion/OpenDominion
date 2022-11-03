<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Race;

class BuildingHelper
{
    public function getBuildingTypes(): array
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

    public function getBuildingName(string $buildingType): string
    {
        // Rename 'Wizard Guild' to 'Guild'
        $buildingType = str_replace('wizard', '', $buildingType);
        return ucwords(str_replace('_', ' ', $buildingType));
    }

    public function getBuildingTypesByRace(Race $race = null): array
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

    public function getBuildingHelpString(string $buildingType): ?string
    {
        $helpStrings = [
            'home' => 'Houses 30 people.',
            'alchemy' => 'Produces 45 platinum per hour.',
            'farm' => 'Produces 80 bushels of food per hour.<br><br>Each person eats 0.25 of a bushel of food per hour.',
            'smithy' => 'Reduces specialist and elite military unit training costs (except Gnome ore costs).<br><br>Training cost reduced by 2% per 1% owned, up to a maximum of 36% at 18% owned.',
            'masonry' => 'Increases castle bonuses by 2.75% per 1% owned.',
            'ore_mine' => 'Produces 60 ore per hour.',
            'gryphon_nest' => 'Increases offensive power.<br><br>Power increased by 1.75% per 1% owned, up to a maximum of 35% at 20% owned.',
            'tower' => 'Produces 25 mana per hour.',
            'wizard_guild' => 'Spy, Wizard, and Archmage training costs reduced by 3.5% per 1% owned, up to a maximum of 35% at 10% owned.<br>Spy and Wizard Strength refresh rate increased by 0.1% per 1% owned, up to a maximum of 1% at 10% owned.<br>Losses on failed black ops reduced by 2.5% per 1% owned, up to a maximum of 25% at 10% owned.',
            'temple' => 'Increases population growth and reduces defensive bonuses of dominions you invade.<br><br>Population growth increased by 6% per 1% owned.<br>Defensive bonuses reduced by 1.5% per 1% owned, up to a maximum of 25% at 16.67% owned.',
            'diamond_mine' => 'Produces 15 gems per hour.',
            'school' => 'Produces (1 - (Schools / Total Land)) research points per hour (minimum of 0.5). Limited to 50% of your total land.',
            'lumberyard' => 'Produces 50 lumber per hour.',
            'forest_haven' => 'Produces 25 lumber per hour.<br>Fireball damage reduced by 10% per 1% owned, up to a maximum of 80% at 8% owned.<br>Disband Spies and Assassinate Wizards damage reduced by 10% per 1% owned, up to a maximum of 50% at 5% owned.',
            'factory' => 'Reduces construction and land rezoning costs.<br><br>Construction costs reduced by 4% per 1% owned, up to a maximum of 60% at 15% owned.<br>Rezoning costs reduced by 4% per 1% owned, up to a maximum of 60% at 15% owned.',
            'guard_tower' => 'Increases defensive power.<br><br>Power increased by 1.75% per 1% owned, up to a maximum of 35% at 20% owned.',
            'shrine' => 'Increases Hero experience gain by 2% per 1% owned, up to a maximum of 20% at 10% owned.<br>Increases hero bonus by 40% per 1% owned, up to a maximum of 400% at 10% owned.',
            'barracks' => 'Houses 36 trained or training military units.<br><br>Does not increase in capacity for population bonuses.',
            'dock' => 'Produces 1 boat every 20 hours on average.<br>Each dock prevents 2.5 of your boats from being sunk (increases by 0.05 each day after Day 25).<br>Produces 40 bushels of food per hour.',
        ];

        return $helpStrings[$buildingType] ?: null;
    }
}
