<?php

namespace OpenDominion\Helpers;

class AIHelper
{
    public function getDailyDPA()
    {
        return [
            '4'  => 5.5,
            '5'  => 8.5,
            '6'  => 10.5,
            '7'  => 12.0,
            '8'  => 15.0,
            '9'  => 17.0,
            '10' => 19.0,
            '11' => 21.5,
            '12' => 24.0,
            '13' => 26.5,
            '14' => 29.0,
            '15' => 31.5,
            '16' => 33.0,
            '17' => 35.0,
            '18' => 36.0,
            '19' => 37.5,
            '20' => 38.5,
            '21' => 40.5,
            '22' => 42.5,
            '23' => 44.0,
            '24' => 46.0,
            '25' => 48.0,
            '26' => 49.5,
            '27' => 51.0,
            '28' => 52.5,
            '29' => 54.0,
            '30' => 55.0,
            '31' => 57.0,
            '32' => 59.0,
            '33' => 60.5,
            '34' => 62.0,
            '35' => 63.5,
            '36' => 65.0,
            '37' => 66.5,
            '38' => 67.0,
            '39' => 68.5,
            '40' => 70.0,
            '41' => 71.0,
            '42' => 72.0,
            '43' => 73.0,
            '44' => 74.0,
            '45' => 75.0,
            '46' => 76.0,
            '47' => 77.0,
            '48' => 78.0,
            '49' => 79.0,
            '50' => 80.0
        ];
    }

    public function getRaceInstructions()
    {
        return [
            'Dwarf' => [
                'active_chance' => '0.40', // 40% chance to log in
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
                        'amount' => 600 // build up to 600, then stop
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
                'active_chance' => '0.40',
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
                        'amount' => 600
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
                'active_chance' => '0.40',
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
                        'amount' => 800
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
                'active_chance' => '0.50',
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
                'active_chance' => '0.40',
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
                        'amount' => 600
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
                'active_chance' => '0.50',
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
                        'land_type' => 'moutain',
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
                'active_chance' => '0.40',
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
                        'amount' => 600
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
                'active_chance' => '0.40',
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
                        'amount' => 600
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
                'active_chance' => '0.40',
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
                        'amount' => 750
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
                'active_chance' => '0.40',
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
                'active_chance' => '0.40',
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
            'Wood Elf' => [
                'active_chance' => '0.40',
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
