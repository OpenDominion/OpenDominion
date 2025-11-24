<?php

namespace OpenDominion\Helpers;

use Illuminate\Support\Collection;

class HeroEncounterHelper
{
    public function getStoryEncounters(): Collection
    {
        return collect([
            [
                'level' => 1,
                'description' => 'You have been attacked by a hungry wolf.',
                'enemies' => [
                    'wolf' => 1
                ],
            ],
            [
                'level' => 2,
                'description' => 'A group of raiders block your path. You must fight them off.',
                'enemies' => [
                    'bandit' => 3
                ],
            ],
            [
                'level' => 3,
                'description' => 'Dark rituals disturb the nearby land.',
                'enemies' => [
                    'imp' => 1,
                    'cultist' => 2
                ],
            ]
        ]);
    }

    public function getEnemies(): Collection
    {
        return collect([
            'wolf' => [
                'name' => 'Wolf',
                'health' => 40,
                'attack' => 10,
                'defense' => 10,
                'evasion' => 0,
                'focus' => 10,
                'counter' => 10,
                'recover' => 20,
                'strategy' => 'aggressive',
            ],
            'bandit' => [
                'name' => 'Bandit',
                'health' => 80,
                'attack' => 20,
                'defense' => 10,
                'evasion' => 10,
                'focus' => 10,
                'counter' => 10,
                'recover' => 20,
                'strategy' => 'counter',
            ],
            'imp' => [
                'name' => 'Imp',
                'health' => 60,
                'attack' => 20,
                'defense' => 10,
                'evasion' => 50,
                'focus' => 10,
                'counter' => 10,
                'recover' => 20,
                'strategy' => 'aggressive',
            ],
            'cultist' => [
                'name' => 'Cultist',
                'health' => 80,
                'attack' => 20,
                'defense' => 10,
                'evasion' => 10,
                'focus' => 20,
                'counter' => 10,
                'recover' => 20,
                'strategy' => 'balanced',
            ],
            'rabid_bunny' => [
                'name' => 'Rabid Bunny',
                'health' => 100,
                'attack' => 40,
                'defense' => 20,
                'evasion' => 50,
                'focus' => 10,
                'counter' => 10,
                'recover' => 40,
                'strategy' => 'balanced',
            ],
            'dragonkin' => [
                'name' => 'Dragonkin',
                'health' => 60,
                'attack' => 40,
                'defense' => 10,
                'evasion' => 0,
                'focus' => 10,
                'counter' => 10,
                'recover' => 20,
                'strategy' => 'balanced',
            ],
            'gate_warden' => [
                'name' => 'Gate Warden',
                'health' => 150,
                'attack' => 40,
                'defense' => 25,
                'evasion' => 10,
                'focus' => 10,
                'counter' => 50,
                'recover' => 20,
                'strategy' => 'counter',
            ],
            'rebel_corsair' => [
                'name' => 'Rebel Corsair',
                'health' => 60,
                'attack' => 35,
                'defense' => 20,
                'evasion' => 0,
                'focus' => 10,
                'counter' => 10,
                'recover' => 20,
                'strategy' => 'pirate',
                'abilities' => ['blade_flurry'],
            ],
            'rebel_admiral' => [
                // TODO: Use this name if not provided elsewhere
                'name' => 'Rebel Admiral',
                'health' => 150,
                'attack' => 40,
                'defense' => 25,
                'evasion' => 0,
                'focus' => 10,
                'counter' => 10,
                'recover' => 20,
                'strategy' => 'pirate',
                'abilities' => ['blade_flurry', 'enrage'],
            ],
            'warrior_king' => [
                'name' => 'The Warrior King',
                'health' => 80,
                'attack' => 30,
                'defense' => 10,
                'evasion' => 10,
                'focus' => 20,
                'counter' => 10,
                'recover' => 20,
                'strategy' => 'aggressive',
                'abilities' => ['undying'],
            ],
            'sorcerer_king' => [
                'name' => 'The Sorcerer King',
                'health' => 60,
                'attack' => 20,
                'defense' => 20,
                'evasion' => 0,
                'focus' => 10,
                'counter' => 10,
                'recover' => 30,
                'strategy' => 'defensive',
                'abilities' => ['undying'],
            ],
            'betrayer_king' => [
                'name' => 'The Betrayer King',
                'health' => 80,
                'attack' => 25,
                'defense' => 15,
                'evasion' => 25,
                'focus' => 10,
                'counter' => 15,
                'recover' => 20,
                'strategy' => 'balanced',
                'abilities' => ['undying'],
            ],
            'eternal_guardian' => [
                'name' => 'The Eternal Guardian',
                'health' => 90,
                'attack' => 30,
                'defense' => 20,
                'evasion' => 0,
                'focus' => 0,
                'counter' => 0,
                'recover' => 10,
                'strategy' => 'summoner',
                'abilities' => ['undying_legion', 'summon_skeleton'],
            ],
            'skeleton_warrior' => [
                'name' => 'Skeleton Warrior',
                'health' => 40,
                'attack' => 28,
                'defense' => 20,
                'evasion' => 0,
                'focus' => 0,
                'counter' => 0,
                'recover' => 0,
                'strategy' => 'attack',
                'abilities' => ['undying'],
            ],
            'nightbringer' => [
                'name' => 'The Nightbringer',
                'health' => 200,
                'attack' => 50,
                'defense' => 20,
                'evasion' => 0,
                'focus' => 0,
                'counter' => 20,
                'recover' => 0,
                'strategy' => 'balanced',
                'abilities' => ['elusive', 'darkness'],
            ],
            'nox_cultist' => [
                'name' => 'Nox Cultist',
                'health' => 60,
                'attack' => 25,
                'defense' => 15,
                'evasion' => 0,
                'focus' => 5,
                'counter' => 10,
                'recover' => 0,
                'strategy' => 'aggressive',
                'abilities' => ['dying_light'],
            ],
        ]);
    }

    public function getEncounters(): Collection
    {
        return collect([
            'rabid_bunny' => [
                'name' => 'Rabid Bunny',
                'source' => 'Seasonal Battle (Round 44)',
                'enemies' => [
                    ['key' => 'rabid_bunny', 'name' => 'Rabid Bunny'],
                ],
            ],
            'dragonkin' => [
                'name' => 'Dragonkin',
                'source' => 'Raid (Lair of the Dragon)',
                'enemies' => [
                    ['key' => 'dragonkin', 'name' => 'Dragonkin #1'],
                    ['key' => 'dragonkin', 'name' => 'Dragonkin #2'],
                    ['key' => 'dragonkin', 'name' => 'Dragonkin #3'],
                ],
            ],
            'gate_warden' => [
                'name' => 'Gate Warden',
                'source' => 'Raid (Ironhold Citadel)',
                'enemies' => [
                    ['key' => 'gate_warden', 'name' => 'Gate Warden'],
                ],
            ],
            'rebel_corsair' => [
                'name' => 'Rebel Corsairs',
                'source' => 'Raid (The Island Fortress)',
                'enemies' => [
                    ['key' => 'rebel_corsair', 'name' => 'Rebel Corsair #1'],
                    ['key' => 'rebel_corsair', 'name' => 'Rebel Corsair #2'],
                    ['key' => 'rebel_corsair', 'name' => 'Rebel Corsair #3'],
                ],
            ],
            'rebel_admiral' => [
                'name' => 'Rebel Admiral',
                'source' => 'Raid (The Island Fortress)',
                'enemies' => [
                    ['key' => 'rebel_admiral', 'name' => 'Rebel Admiral'],
                ],
            ],
            'fallen_kings' => [
                'name' => 'The Fallen Kings',
                'source' => 'Raid (The Tomb of Kings)',
                'enemies' => [
                    ['key' => 'betrayer_king', 'name' => 'The Betrayer King'],
                    ['key' => 'sorcerer_king', 'name' => 'The Sorcerer King'],
                    ['key' => 'warrior_king', 'name' => 'The Warrior King'],
                ],
            ],
            'eternal_guardian' => [
                'name' => 'The Guardian of the Throne',
                'source' => 'Raid (The Tomb of Kings)',
                'enemies' => [
                    ['key' => 'eternal_guardian', 'name' => 'The Eternal Guardian'],
                ],
            ],
            'nightbringer' => [
                'name' => 'The Nightbringer',
                'source' => 'Raid (Rise of the Nightbringer)',
                'enemies' => [
                    ['key' => 'nightbringer', 'name' => 'The Nightbringer'],
                    ['key' => 'nox_cultist', 'name' => 'Nox Cultist #1'],
                    ['key' => 'nox_cultist', 'name' => 'Nox Cultist #2'],
                ],
            ],
        ]);
    }
}
