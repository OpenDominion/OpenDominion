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
            //'forest_haven',
            'factory',
            'guard_tower',
            'shrine',
            'barracks',
            'dock',
        ];
    }

    public function getBuildingName(string $buildingType): string
    {
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
                //'forest_haven',
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
            'home' => 'Houses 30 people.<br><br>Does not employ peasants.',
            'alchemy' => 'Produces 45 platinum per hour.',
            'farm' => 'Produces 80 bushels of food per hour.<br><br>Each person eats 0.25 of one bushel of food per hour.',
            'smithy' => 'Training cost reduced by 2% per 1% owned, up to a maximum of 36% at 18% owned.<br><br>Does not affect Gnome ore costs.',
            'masonry' => 'Increases castle bonuses by 2.6% per 1% owned.',
            'ore_mine' => 'Produces 60 ore per hour.',
            'gryphon_nest' => 'Offensive power increased by 1.75% per 1% owned, up to a maximum of 35% at 20% owned.',
            'tower' => 'Produces 25 mana per hour.',
            'wizard_guild' => 'Produces 5 mana per hour.<br><br>Each Wizard Guild increases the number of peasants protected from Fireball to 20 (from 5) for up to 6 wizards (max 120 per Guild).<br><br>Reduces damage from Lightning Bolt by 10% per 1% owned, up to a maximum of 50% at 5% owned.',
            'temple' => 'Population growth increased by 6% per 1% owned.<br>Enemy defensive power reduced by 1.5% per 1% owned, up to a maximum of 25% at 16.67% owned.',
            'diamond_mine' => 'Produces 15 gems per hour.',
            'school' => 'Produces (1 - (Schools / Total Land)) research points per hour (minimum of 0.5). Limited to 50% of your total land.',
            'lumberyard' => 'Produces 50 lumber per hour.',
            //'forest_haven' => 'Produces 25 lumber per hour.<br>Protects 6.25% of your vulnerable peasant population from Fireball per 1% owned, up to a maximum of 50% at 8% owned.',
            'factory' => 'Construction costs reduced by 5% per 1% owned, up to a maximum of 50% at 10% owned.<br>Rezoning costs reduced by 5% per 1% owned, up to a maximum of 50% at 10% owned.<br>Employs 25 peasants (instead of 20).',
            'guard_tower' => 'Defensive power increased by 1.75% per 1% owned, up to a maximum of 35% at 20% owned.',
            'shrine' => 'Increases Hero experience gain by 2% per 1% owned, up to a maximum of 20% at 10% owned.<br>Increases hero bonus by 50% per 1% owned, up to a maximum of 500% at 10% owned.',
            'barracks' => 'Houses 36 trained or in training military units.<br><br>Does not employ peasants or increase in capacity due to population bonuses.',
            'dock' => 'Produces 1 boat every 20 hours on average.<br>Each dock prevents 2.25 of your boats from being sunk (increases by 0.05 each day of the round).<br>Produces 40 bushels of food per hour.',
        ];

        return $helpStrings[$buildingType] ?: null;
    }
}
