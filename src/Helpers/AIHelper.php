<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Round;

class AIHelper
{
    public function getDefenseForNonPlayer(Round $round)
    {
        $day = $round->daysInRound();
        $hours = $round->hoursInDay();
        $fractionalDay = $day + ($hours / 24);

        // Formula based on average DPA of attacks over several rounds
        $defensePerAcre = (-0.0181 * ($fractionalDay**2)) + (2.5797 * $fractionalDay) - 4.1725;
        // Additional defense for first few days
        $defensePerAcre += max(0, 5 - $fractionalDay/2);

        return $defensePerAcre;
    }

    public function getRaceInstructions()
    {
        return [
            'Dwarf' => [
                'active_chance' => '0.33', // 33% chance to log in
                'invest' => 'ore', // alternate resource to invest
                'spells' => [
                    'miners_sight',
                    'ares_call',
                    'midas_touch'
                ],
                'build' => [
                    [
                        'land_type' => 'plain',
                        'building' => 'farm',
                        'amount' => 0.07 // maintain 7% farms
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
                    ],
                    [
                        'land_type' => 'mountain',
                        'building' => 'ore_mine',
                        'amount' => 500 // build up to 600, then stop
                    ],
                    [
                        'land_type' => 'mountain',
                        'building' => 'home',
                        'amount' => -1 // no limit, when jobs available
                    ],
                    [
                        'land_type' => 'plain',
                        'building' => 'masonry',
                        'amount' => -1 // no limit, when jobs needed
                    ]
                ],
                'military' => [
                    [
                        'unit' => 'unit2',
                        'amount' => -1
                    ],
                    [
                        'unit' => 'spies',
                        'amount' => 0.05 // maintain 0.05 SPA
                    ],
                    [
                        'unit' => 'wizards',
                        'amount' => 0.05 // maintain 0.05 WPA
                    ]
                ]
            ],
            'Firewalker' => [
                'active_chance' => '0.33',
                'invest' => 'gems',
                'spells' => [
                    'alchemist_flame',
                    'ares_call',
                    'midas_touch'
                ],
                'build' => [
                    [
                        'land_type' => 'plain',
                        'building' => 'farm',
                        'amount' => 0.07
                    ],
                    [
                        'land_type' => 'swamp',
                        'building' => 'tower',
                        'amount' => 0.05
                    ],
                    [
                        'land_type' => 'forest',
                        'building' => 'lumberyard',
                        'amount' => 0.04
                    ],
                    [
                        'land_type' => 'cavern',
                        'building' => 'diamond_mine',
                        'amount' => 500
                    ],
                    [
                        'land_type' => 'cavern',
                        'building' => 'home',
                        'amount' => -1
                    ],
                    [
                        'land_type' => 'plain',
                        'building' => 'alchemy',
                        'amount' => -1
                    ]
                ],
                'military' => [
                    [
                        'unit' => 'unit2',
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
            ],
            'Goblin' => [
                'active_chance' => '0.33',
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
                        'land_type' => 'swamp',
                        'building' => 'tower',
                        'amount' => 0.04
                    ],
                    [
                        'land_type' => 'forest',
                        'building' => 'lumberyard',
                        'amount' => 0.035
                    ],
                    [
                        'land_type' => 'mountain',
                        'building' => 'ore_mine',
                        'amount' => 0.03
                    ],
                    [
                        'land_type' => 'cavern',
                        'building' => 'diamond_mine',
                        'amount' => 600
                    ],
                    [
                        'land_type' => 'hill',
                        'building' => 'home',
                        'amount' => -1
                    ],
                    [
                        'land_type' => 'hill',
                        'building' => 'guard_tower',
                        'amount' => -1
                    ]
                ],
                'military' => [
                    [
                        'unit' => 'unit2',
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
            ],
            'Halfling' => [
                'active_chance' => '0.33',
                'invest' => 'gems',
                'spells' => [
                    'defensive_frenzy',
                    'midas_touch'
                ],
                'build' => [
                    [
                        'land_type' => 'plain',
                        'building' => 'farm',
                        'amount' => 0.07
                    ],
                    [
                        'land_type' => 'swamp',
                        'building' => 'tower',
                        'amount' => 0.045
                    ],
                    [
                        'land_type' => 'forest',
                        'building' => 'lumberyard',
                        'amount' => 0.035
                    ],
                    [
                        'land_type' => 'mountain',
                        'building' => 'ore_mine',
                        'amount' => 0.03
                    ],
                    [
                        'land_type' => 'cavern',
                        'building' => 'diamond_mine',
                        'amount' => 500
                    ],
                    [
                        'land_type' => 'hill',
                        'building' => 'home',
                        'amount' => -1
                    ],
                    [
                        'land_type' => 'hill',
                        'building' => 'guard_tower',
                        'amount' => -1
                    ]
                ],
                'military' => [
                    [
                        'unit' => 'unit2',
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
            ],
            'Human' => [
                'active_chance' => '0.33',
                'invest' => 'gems',
                'spells' => [
                    'ares_call',
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
                        'amount' => 0.04
                    ],
                    [
                        'land_type' => 'forest',
                        'building' => 'lumberyard',
                        'amount' => 0.035
                    ],
                    [
                        'land_type' => 'mountain',
                        'building' => 'ore_mine',
                        'amount' => 0.05
                    ],
                    [
                        'land_type' => 'cavern',
                        'building' => 'diamond_mine',
                        'amount' => 500
                    ],
                    [
                        'land_type' => 'plain',
                        'building' => 'home',
                        'amount' => -1
                    ],
                    [
                        'land_type' => 'hill',
                        'building' => 'factory',
                        'amount' => -1
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
            ],
            'Icekin' => [
                'active_chance' => '0.33',
                'invest' => 'ore',
                'spells' => [
                    'blizzard',
                    'midas_touch'
                ],
                'build' => [
                    [
                        'land_type' => 'plain',
                        'building' => 'farm',
                        'amount' => 0.065
                    ],
                    [
                        'land_type' => 'swamp',
                        'building' => 'tower',
                        'amount' => 0.045
                    ],
                    [
                        'land_type' => 'forest',
                        'building' => 'lumberyard',
                        'amount' => 0.04
                    ],
                    [
                        'land_type' => 'mountain',
                        'building' => 'ore_mine',
                        'amount' => 0.6
                    ],
                    [
                        'land_type' => 'mountain',
                        'building' => 'home',
                        'amount' => -1
                    ],
                    [
                        'land_type' => 'mountain',
                        'building' => 'ore_mine',
                        'amount' => -1
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
            ],
            'Lizardfolk' => [
                'active_chance' => '0.33',
                'invest' => 'gems',
                'spells' => [
                    'ares_call',
                    'midas_touch'
                ],
                'build' => [
                    [
                        'land_type' => 'plain',
                        'building' => 'farm',
                        'amount' => 0.07
                    ],
                    [
                        'land_type' => 'swamp',
                        'building' => 'tower',
                        'amount' => 0.04
                    ],
                    [
                        'land_type' => 'forest',
                        'building' => 'lumberyard',
                        'amount' => 0.035
                    ],
                    [
                        'land_type' => 'cavern',
                        'building' => 'diamond_mine',
                        'amount' => 500
                    ],
                    [
                        'land_type' => 'water',
                        'building' => 'home',
                        'amount' => -1
                    ],
                    [
                        'land_type' => 'hill',
                        'building' => 'guard_tower',
                        'amount' => -1
                    ]
                ],
                'military' => [
                    [
                        'unit' => 'unit2',
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
            ],
            'Lycanthrope' => [
                'active_chance' => '0.33',
                'invest' => 'gems',
                'spells' => [
                    'ares_call',
                    'midas_touch'
                ],
                'build' => [
                    [
                        'land_type' => 'plain',
                        'building' => 'farm',
                        'amount' => 0.07
                    ],
                    [
                        'land_type' => 'swamp',
                        'building' => 'tower',
                        'amount' => 0.04
                    ],
                    [
                        'land_type' => 'forest',
                        'building' => 'lumberyard',
                        'amount' => 0.035
                    ],
                    [
                        'land_type' => 'mountain',
                        'building' => 'ore_mine',
                        'amount' => 0.03
                    ],
                    [
                        'land_type' => 'cavern',
                        'building' => 'diamond_mine',
                        'amount' => 500
                    ],
                    [
                        'land_type' => 'forest',
                        'building' => 'home',
                        'amount' => -1
                    ],
                    [
                        'land_type' => 'hill',
                        'building' => 'guard_tower',
                        'amount' => -1
                    ]
                ],
                'military' => [
                    [
                        'unit' => 'unit2',
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
            ],
            'Merfolk' => [
                'active_chance' => '0.33',
                'invest' => 'gems',
                'spells' => [
                    'ares_call',
                    'midas_touch'
                ],
                'build' => [
                    [
                        'land_type' => 'plain',
                        'building' => 'farm',
                        'amount' => 0.065
                    ],
                    [
                        'land_type' => 'swamp',
                        'building' => 'tower',
                        'amount' => 0.04
                    ],
                    [
                        'land_type' => 'forest',
                        'building' => 'lumberyard',
                        'amount' => 0.045
                    ],
                    [
                        'land_type' => 'cavern',
                        'building' => 'diamond_mine',
                        'amount' => 500
                    ],
                    [
                        'land_type' => 'water',
                        'building' => 'home',
                        'amount' => -1
                    ],
                    [
                        'land_type' => 'plain',
                        'building' => 'masonry',
                        'amount' => -1
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
            ],
            'Nomad' => [
                'active_chance' => '0.33',
                'invest' => 'gems',
                'spells' => [
                    'ares_call',
                    'midas_touch'
                ],
                'build' => [
                    [
                        'land_type' => 'plain',
                        'building' => 'farm',
                        'amount' => 0.065
                    ],
                    [
                        'land_type' => 'swamp',
                        'building' => 'tower',
                        'amount' => 0.04
                    ],
                    [
                        'land_type' => 'forest',
                        'building' => 'lumberyard',
                        'amount' => 0.035
                    ],
                    [
                        'land_type' => 'mountain',
                        'building' => 'ore_mine',
                        'amount' => 0.05
                    ],
                    [
                        'land_type' => 'cavern',
                        'building' => 'diamond_mine',
                        'amount' => 600
                    ],
                    [
                        'land_type' => 'plain',
                        'building' => 'home',
                        'amount' => -1
                    ],
                    [
                        'land_type' => 'hill',
                        'building' => 'shrine',
                        'amount' => -1
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
            ],
            'Sylvan' => [
                'active_chance' => '0.33',
                'invest' => 'lumber',
                'spells' => [
                    'ares_call',
                    'midas_touch'
                ],
                'build' => [
                    [
                        'land_type' => 'plain',
                        'building' => 'farm',
                        'amount' => 0.065
                    ],
                    [
                        'land_type' => 'swamp',
                        'building' => 'tower',
                        'amount' => 0.04
                    ],
                    [
                        'land_type' => 'forest',
                        'building' => 'lumberyard',
                        'amount' => 0.05
                    ],
                    [
                        'land_type' => 'forest',
                        'building' => 'home',
                        'amount' => -1
                    ],
                    [
                        'land_type' => 'forest',
                        'building' => 'lumberyard',
                        'amount' => -1
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
            ],
            'Troll' => [
                'active_chance' => '0.33',
                'invest' => 'gems',
                'spells' => [
                    'ares_call',
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
                        'amount' => 0.04
                    ],
                    [
                        'land_type' => 'forest',
                        'building' => 'lumberyard',
                        'amount' => 0.035
                    ],
                    [
                        'land_type' => 'plain',
                        'building' => 'home',
                        'amount' => -1
                    ],
                    [
                        'land_type' => 'plain',
                        'building' => 'smithy',
                        'amount' => -1
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
            ],
            'Wood Elf' => [
                'active_chance' => '0.33',
                'invest' => 'lumber',
                'spells' => [
                    'gaias_blessing',
                    'ares_call',
                    'midas_touch'
                ],
                'build' => [
                    [
                        'land_type' => 'plain',
                        'building' => 'farm',
                        'amount' => 0.065
                    ],
                    [
                        'land_type' => 'swamp',
                        'building' => 'tower',
                        'amount' => 0.05
                    ],
                    [
                        'land_type' => 'forest',
                        'building' => 'lumberyard',
                        'amount' => 0.05
                    ],
                    [
                        'land_type' => 'forest',
                        'building' => 'home',
                        'amount' => -1
                    ],
                    [
                        'land_type' => 'forest',
                        'building' => 'lumberyard',
                        'amount' => -1
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
            ]
        ];
    }
}
