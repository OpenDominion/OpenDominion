<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Race;
use OpenDominion\Models\Round;

class AIHelper
{
    public function getDefenseForNonPlayer(Round $round, int $totalLand)
    {
        $day = $round->daysInRound();
        $hours = $round->hoursInDay();
        $fractionalDay = $day + ($hours / 24);
        $expectedLandSize = 410 + ($fractionalDay * 40);

        // Formula based on average DPA of attacks over several rounds
        $defensePerAcre = (-0.0181 * ($fractionalDay**2)) + (2.5797 * $fractionalDay) - 4.1725;
        // Additional defense for first few days
        $defensePerAcre += max(0, 5 - $fractionalDay/2);
        // Scale by expected land size
        $scaleFactor = clamp(0.80, $totalLand / $expectedLandSize, 1.20);

        return $totalLand * $defensePerAcre * $scaleFactor;
    }

    public function generateConfig(Race $race)
    {
        $config = $this->getDefaultInstructions();

        $config['active_chance'] = mt_rand(25, 40) / 100;
        $config['max_land'] = (int)skewed_distribution(400, 3000);

        $investOreRaces = ['Dwarf', 'Gnome', 'Icekin'];
        if (in_array($race->name, $investOreRaces)) {
            $config['invest'] = 'ore';
        }
        $investLumberRaces = ['Sylvan', 'Wood Elf'];
        if (in_array($race->name, $investLumberRaces)) {
            $config['invest'] = 'lumber';
        }

        if ($race->name == 'Halfling') {
            $config['spells'][] = 'defensive_frenzy';
        } elseif ($race->name == 'Icekin') {
            $config['spells'][] = 'blizzard';
        } else {
            $config['spells'][] = 'ares_call';
        }

        $racesWithoutOre = ['Firewalker', 'Lizardfolk', 'Merfolk', 'Nox', 'Spirit', 'Sylvan', 'Undead'];
        if (!in_array($race->name, $racesWithoutOre)) {
            $config['build'][] = [
                'land_type' => 'mountain',
                'building' => 'ore_mine',
                'amount' => 0.06
            ];
        }

        $specOnlyRaces = ['Goblin', 'Halfling', 'Lizardfolk'];
        if (in_array($race->name, $specOnlyRaces) || random_chance(0.4)) {
            $config['military'][0]['unit'] = 'unit2';
        }

        $landBasedRaces = ['Gnome', 'Icekin', 'Nox', 'Sylvan', 'Wood Elf'];
        if (in_array($race->name, $landBasedRaces)) {
            if ($race->name == 'Nox') {
                $config['build'][] = [
                    'land_type' => 'swamp',
                    'building' => 'wizard_guild',
                    'amount' => mt_rand(8, 15) / 100
                ];
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

            $config['build'][] = $jobBuildings->random();
        }

        return $config;
    }

    public function getDefaultInstructions()
    {
        return [
            'active_chance' => '0.25',
            'max_land' => 3000,
            'invest' => 'gems',
            'spells' => [
                'midas_touch'
            ],
            'build' => [
                [
                    'land_type' => 'plain',
                    'building' => 'farm',
                    'amount' => 0.06
                ],
                [
                    'land_type' => 'swamp',
                    'building' => 'tower',
                    'amount' => 0.05
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
                    'amount' => 0.05
                ],
                [
                    'unit' => 'wizards',
                    'amount' => 0.05
                ]
            ]
        ];
    }
}
