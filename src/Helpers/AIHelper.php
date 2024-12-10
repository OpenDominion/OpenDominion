<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Race;
use OpenDominion\Models\Round;

class AIHelper
{
    protected function getFractionalDay(Round $round): float
    {
        $day = $round->daysInRound() + 3;
        $hours = $round->hoursInDay();
        $fractionalDay = $day + ($hours / 24);

        return $fractionalDay;
    }

    public function getTopOffense(Round $round): float
    {
        $fractionalDay = $this->getFractionalDay($round);

        // Formula based on average DP of attacks over several rounds
        return (0.039 * $fractionalDay**4) - (0.45 * $fractionalDay**3) + (12.3 * $fractionalDay**2) + (5950 * $fractionalDay) - 19400;
    }

    public function getExpectedLandSize(Round $round): float
    {
        $fractionalDay = $this->getFractionalDay($round);

        // Approximate land size at max growth rate
        return (0.063 * $fractionalDay**3) - (4.8 * $fractionalDay**2) + (181 * $fractionalDay) - 116;
    }

    public function getDefenseForNonPlayer(Round $round, int $totalLand): float
    {
        $topOffense = $this->getTopOffense($round);
        $expectedLandSize = $this->getExpectedLandSize($round);
        $defenseRequired = $topOffense * (($expectedLandSize / $totalLand) ** -1.375);

        return $defenseRequired;
    }

    public function generateConfig(Race $race)
    {
        $config = $this->getDefaultInstructions();

        $config['active_chance'] = mt_rand(25, 40) / 100;
        $config['max_land'] = mt_rand(450, 3500);
        $config['elite_guard_land'] = mt_rand(2000, 3000);

        $investOreRaces = ['Dwarf', 'Gnome', 'Icekin'];
        if (in_array($race->name, $investOreRaces)) {
            $config['invest'] = 'ore';
        }
        $investLumberRaces = ['Sylvan', 'Wood Elf'];
        if (in_array($race->name, $investLumberRaces)) {
            $config['invest'] = 'lumber';
        }

        $racesWithManaUnits = ['Nox', 'Spirit', 'Undead'];
        if (in_array($race->name, $racesWithManaUnits)) {
            $config['build'][] = [
                'land_type' => 'swamp',
                'building' => 'tower',
                'amount' => 0.09
            ];
        } else {
            $config['build'][] = [
                'land_type' => 'swamp',
                'building' => 'tower',
                'amount' => 0.05
            ];
        }

        $racesWithoutOre = ['Firewalker', 'Lizardfolk', 'Merfolk', 'Nox', 'Spirit', 'Sylvan', 'Undead', 'Vampire'];
        if (!in_array($race->name, $racesWithoutOre)) {
            $oreMinePercentage = 0.06;
            if ($race->name == 'Troll') {
                $oreMinePercentage = 0.09;
            }
            $config['build'][] = [
                'land_type' => 'mountain',
                'building' => 'ore_mine',
                'amount' => $oreMinePercentage
            ];
        }

        $eliteOnlyRaces = ['Troll'];
        if (in_array($race->name, $eliteOnlyRaces)) {
            $config['military'][0]['unit'] = 'unit4';
        }

        $specOnlyRaces = ['Goblin', 'Halfling', 'Lizardfolk'];
        if (in_array($race->name, $specOnlyRaces) || random_chance(0.4)) {
            $config['military'][0]['unit'] = 'unit2';
        }

        $config['build'][] = [
            'land_type' => $race->home_land_type,
            'building' => 'home',
            'amount' => -1
        ];

        $jobBuildings = collect([
            [
                'land_type' => 'plain',
                'building' => 'alchemy',
                'amount' => -1
            ],
            [
                'land_type' => 'plain',
                'building' => 'masonry',
                'amount' => -1
            ],
            [
                'land_type' => 'cavern',
                'building' => 'school',
                'amount' => -1
            ],
            [
                'land_type' => 'hill',
                'building' => 'shrine',
                'amount' => -1
            ],
            [
                'land_type' => 'hill',
                'building' => 'factory',
                'amount' => -1
            ]
        ]);

        $landBasedRaces = ['Gnome', 'Icekin', 'Nox', 'Sylvan', 'Wood Elf'];
        if (in_array($race->name, $landBasedRaces)) {
            if ($race->name == 'Nox') {
                $config['build'][] = [
                    'land_type' => 'swamp',
                    'building' => 'wizard_guild',
                    'amount' => mt_rand(8, 15) / 100
                ];

                $config['build'][] = $jobBuildings->random();
            }
            if (in_array($race->name, ['Gnome', 'Icekin'])) {
                $config['build'][] = [
                    'land_type' => 'mountain',
                    'building' => 'ore_mine',
                    'amount' => -1
                ];
            }
            if (in_array($race->name, ['Sylvan', 'Wood Elf'])) {
                $config['build'][] = [
                    'land_type' => 'forest',
                    'building' => 'lumberyard',
                    'amount' => -1
                ];
            }
        } else {
            if ($config['military'][0]['unit'] == 'unit2' && random_chance(0.75)) {
                $config['build'][] = [
                    'land_type' => 'hill',
                    'building' => 'guard_tower',
                    'amount' => mt_rand(10, 20) / 100
                ];
            } else {
                $config['build'][] = [
                    'land_type' => 'plain',
                    'building' => 'smithy',
                    'amount' => mt_rand(5, 18) / 100
                ];
            }

            $config['build'][] = [
                'land_type' => 'cavern',
                'building' => 'diamond_mine',
                'amount' => mt_rand(50, 150)
            ];

            $config['build'][] = $jobBuildings->random();
        }

        return $config;
    }

    public function getDefaultInstructions()
    {
        return [
            'active_chance' => '0.25',
            'max_land' => 3500,
            'elite_guard_land' => 3000,
            'invest' => 'gems',
            'spells' => [
                'ares_call',
                'midas_touch'
            ],
            'build' => [
                [
                    'land_type' => 'plain',
                    'building' => 'farm',
                    'amount' => 0.08
                ],
                [
                    'land_type' => 'forest',
                    'building' => 'lumberyard',
                    'amount' => 0.035
                ]
            ],
            'military' => [
                [
                    'unit' => 'unit3',
                    'amount' => -1
                ],
                [
                    'unit' => 'spies',
                    'amount' => mt_rand(10, 25) / 1000
                ],
                [
                    'unit' => 'wizards',
                    'amount' => mt_rand(10, 25) / 1000
                ]
            ]
        ];
    }
}
