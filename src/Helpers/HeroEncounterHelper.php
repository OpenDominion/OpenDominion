<?php

namespace OpenDominion\Helpers;

use Illuminate\Support\Collection;

class HeroEncounterHelper
{
    public function getEncounters(): Collection
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
                    'fire_imp' => 1,
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
            'fire_imp' => [
                'name' => 'Fire Imp',
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
        ]);
    }

    public function getPracticeBattles(): Collection
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
        ]);
    }
}
