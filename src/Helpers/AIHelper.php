<?php

namespace OpenDominion\Helpers;

use Carbon\Carbon;
use InvalidArgumentException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Models\Unit;

class AIHelper
{
    public const STRATEGY_ATTACKER = 'attacker';

    public const STRATEGY_EXPLORER = 'explorer';

    /**
     * Docks and boats given to newly spawned attackers whose offensive units need boats.
     */
    public const ATTACKER_STARTING_DOCKS = 20;

    public const ATTACKER_STARTING_BOATS = 36;

    public const ATTACKER_SMITHY_PERCENTAGE = 0.18;

    /**
     * Offensive unit trained alongside each defensive unit by attacking non-player dominions.
     */
    protected const ATTACKER_UNIT_PAIRS = [
        'unit2' => 'unit4',
        'unit3' => 'unit1',
    ];

    /**
     * Races supported as attacking non-player dominions, keyed by race name.
     *
     * attack_spells: racial self spells cast only right before invading
     * military: fixed starting defensive unit (randomized when omitted)
     * unit_pairs: race-specific overrides for ATTACKER_UNIT_PAIRS
     * unit_swap: switches the unit composition once prestige reaches a threshold
     */
    protected const ATTACKER_RACE_SETTINGS = [
        'Orc' => [
            'attack_spells' => ['bloodrage'],
            'military' => 'unit3',
            'unit_swap' => [
                'prestige' => 600,
                'military' => 'unit2',
                'offense' => 'unit4',
            ],
        ],
        'Spirit' => [
            'attack_spells' => ['unholy_ghost'],
        ],
    ];

    public function __construct(protected LandHelper $landHelper)
    {
    }

    protected function getFractionalDay(Round $round, ?Carbon $datetime = null): float
    {
        $day = $round->daysInRound($datetime) + 3;
        $hours = $round->hoursInDay($datetime);
        $fractionalDay = $day + ($hours / 24);

        return $fractionalDay;
    }

    public function getTopOffense(Round $round, ?Carbon $datetime = null): float
    {
        $fractionalDay = $this->getFractionalDay($round, $datetime);

        // Formula based on average DP of attacks over several rounds
        return (0.039 * $fractionalDay**4) - (0.45 * $fractionalDay**3) + (12.3 * $fractionalDay**2) + (5950 * $fractionalDay) - 19400;
    }

    public function getExpectedLandSize(Round $round, ?Carbon $datetime = null): float
    {
        $fractionalDay = $this->getFractionalDay($round, $datetime);

        // Approximate land size at max growth rate
        return (0.063 * $fractionalDay**3) - (4.8 * $fractionalDay**2) + (181 * $fractionalDay) - 116;
    }

    /**
     * Returns the defense a non-player dominion of the given size aims for, at $datetime (defaults to now).
     */
    public function getDefenseForNonPlayer(Round $round, int $totalLand, ?Carbon $datetime = null): float
    {
        $topOffense = $this->getTopOffense($round, $datetime);
        $expectedLandSize = $this->getExpectedLandSize($round, $datetime);
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

        $racesWithoutOre = ['Firewalker', 'Lizardfolk', 'Merfolk', 'Nox', 'Planewalker', 'Spirit', 'Sylvan', 'Undead', 'Vampire'];
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

        $eliteOnlyRaces = ['Planewalker', 'Troll'];
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

        $jobBuildings = collect($this->getJobBuildings());

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

    /**
     * Returns the names of races supported as attacking non-player dominions.
     *
     * @return array<int, string>
     */
    public function getAttackerRaces(): array
    {
        return array_keys(self::ATTACKER_RACE_SETTINGS);
    }

    /**
     * Returns attribute changes that give a newly spawned attacker its starting offense.
     *
     * Smithies are topped up to the build plan percentage, and races whose offensive units need
     * boats also get boats and docks. New buildings are converted from the most common other
     * buildings (never homes) so total land and building counts are unchanged.
     *
     * @return array<string, int>
     */
    public function getAttackerStartingAttributes(Dominion $dominion): array
    {
        $attributes = [
            'military_unit1' => mt_rand(300, 350),
        ];

        $landTypesByBuilding = $this->landHelper->getLandTypesByBuildingType($dominion->race);
        $buildings = collect($landTypesByBuilding)->mapWithKeys(fn (string $landType, string $building) => [
            $building => (int) $dominion->{"building_{$building}"},
        ])->all();
        $land = collect($this->landHelper->getLandTypes())->mapWithKeys(fn (string $landType) => [
            $landType => (int) $dominion->{"land_{$landType}"},
        ])->all();

        if ($this->attackerNeedsBoats($dominion->race)) {
            $attributes['resource_boats'] = max($dominion->resource_boats, self::ATTACKER_STARTING_BOATS);
            $this->convertBuildings($buildings, $land, $landTypesByBuilding, 'dock', self::ATTACKER_STARTING_DOCKS - $buildings['dock']);
        }

        $smithyTarget = (int) rceil(self::ATTACKER_SMITHY_PERCENTAGE * array_sum($land));
        $this->convertBuildings($buildings, $land, $landTypesByBuilding, 'smithy', $smithyTarget - $buildings['smithy']);

        foreach ($buildings as $building => $amount) {
            if ($amount !== (int) $dominion->{"building_{$building}"}) {
                $attributes["building_{$building}"] = $amount;
            }
        }
        foreach ($land as $landType => $amount) {
            if ($amount !== (int) $dominion->{"land_{$landType}"}) {
                $attributes["land_{$landType}"] = $amount;
            }
        }

        return $attributes;
    }

    /**
     * Converts buildings (and their land) into a target building, taking from the most common eligible buildings first.
     *
     * @param array<string, int> $buildings
     * @param array<string, int> $land
     * @param array<string, string> $landTypesByBuilding
     */
    protected function convertBuildings(array &$buildings, array &$land, array $landTypesByBuilding, string $targetBuilding, int $amount): void
    {
        $protectedBuildings = ['home', 'dock', 'smithy'];

        while ($amount > 0) {
            $source = collect($buildings)
                ->reject(fn (int $count, string $building) => in_array($building, $protectedBuildings) || $count <= 0)
                ->sortDesc()
                ->keys()
                ->first();

            if ($source === null) {
                return;
            }

            $converted = min($amount, $buildings[$source]);
            $buildings[$source] -= $converted;
            $land[$landTypesByBuilding[$source]] -= $converted;
            $buildings[$targetBuilding] += $converted;
            $land[$landTypesByBuilding[$targetBuilding]] += $converted;
            $amount -= $converted;
        }
    }

    /**
     * Returns additional unit1 queued in training for a newly spawned attacker, keyed by hours until arrival.
     *
     * @return array<int, int>
     */
    public function getAttackerIncomingOffense(): array
    {
        $total = mt_rand(300, 350);
        $hours = collect(range(4, 9))->random(mt_rand(2, 5))->sort()->values();

        $incoming = [];
        $perHour = intdiv($total, $hours->count());
        foreach ($hours as $hour) {
            $incoming[$hour] = $perHour;
        }
        $incoming[$hours->last()] += $total - ($perHour * $hours->count());

        return $incoming;
    }

    /**
     * Returns racial self spells an attacker casts only right before invading.
     *
     * @return array<int, string>
     */
    public function getAttackerAttackSpells(Race $race): array
    {
        return self::ATTACKER_RACE_SETTINGS[$race->name]['attack_spells'] ?? [];
    }

    public function attackerNeedsBoats(Race $race): bool
    {
        $offensiveSlots = $this->getAttackerOffensiveSlots($race);

        return $race->units->contains(fn (Unit $unit) => in_array($unit->slot, $offensiveSlots) && $unit->need_boat);
    }

    public function isAttackerRace(Race $race): bool
    {
        return isset(self::ATTACKER_RACE_SETTINGS[$race->name]);
    }

    /**
     * Returns the offensive unit paired with each defensive unit for a race.
     *
     * @return array<string, string>
     */
    public function getAttackerUnitPairs(Race $race): array
    {
        return array_merge(
            self::ATTACKER_UNIT_PAIRS,
            self::ATTACKER_RACE_SETTINGS[$race->name]['unit_pairs'] ?? []
        );
    }

    public function getAttackerOffensiveUnit(Race $race, string $defensiveUnit): string
    {
        $unitPairs = $this->getAttackerUnitPairs($race);

        if (!isset($unitPairs[$defensiveUnit])) {
            throw new InvalidArgumentException(sprintf('No offensive unit is paired with %s for %s', $defensiveUnit, $race->name));
        }

        return $unitPairs[$defensiveUnit];
    }

    /**
     * Returns the unit slots an attacking non-player dominion sends on invasions.
     *
     * @return array<int, int>
     */
    public function getAttackerOffensiveSlots(Race $race): array
    {
        return collect($this->getAttackerUnitPairs($race))
            ->map(function (string $unit) {
                return (int) str_replace('unit', '', $unit);
            })
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Generates instructions for a non-player dominion that trains offense and invades other bots.
     *
     * @throws InvalidArgumentException
     */
    public function generateAttackerConfig(Race $race): array
    {
        if (!$this->isAttackerRace($race)) {
            throw new InvalidArgumentException(sprintf('%s is not supported as an attacking non-player dominion', $race->name));
        }

        $settings = self::ATTACKER_RACE_SETTINGS[$race->name];
        $config = $this->getDefaultInstructions();

        $config['strategy'] = self::STRATEGY_ATTACKER;
        $config['active_chance'] = mt_rand(25, 40) / 100;
        $config['max_land'] = mt_rand(3000, 4000);
        $config['elite_guard_land'] = mt_rand(2000, 3000);
        $config['min_range'] = 75;
        $config['attack_spells'] = $this->getAttackerAttackSpells($race);

        $defensiveUnit = $settings['military'] ?? (random_chance(0.4) ? 'unit2' : 'unit3');
        $config['military'][0]['unit'] = $defensiveUnit;
        $config['offense'] = $this->getAttackerOffensiveUnit($race, $defensiveUnit);
        if (isset($settings['unit_swap'])) {
            $config['unit_swap'] = $settings['unit_swap'];
        }

        $units = $race->units;

        $config['build'][] = [
            'land_type' => 'swamp',
            'building' => 'tower',
            'amount' => $units->contains(fn (Unit $unit) => $unit->cost_mana > 0) ? 0.09 : 0.05
        ];

        if ($units->contains(fn (Unit $unit) => $unit->cost_ore > 0)) {
            $config['build'][] = [
                'land_type' => 'mountain',
                'building' => 'ore_mine',
                'amount' => 0.06
            ];
        }

        $config['build'][] = [
            'land_type' => 'plain',
            'building' => 'smithy',
            'amount' => self::ATTACKER_SMITHY_PERCENTAGE
        ];

        if ($this->attackerNeedsBoats($race)) {
            $config['build'][] = [
                'land_type' => 'water',
                'building' => 'dock',
                'amount' => mt_rand(30, 50)
            ];
        }

        $config['build'][] = [
            'land_type' => 'cavern',
            'building' => 'diamond_mine',
            'amount' => mt_rand(50, 150)
        ];

        $config['build'][] = [
            'land_type' => 'hill',
            'building' => 'barracks',
            'amount' => -1
        ];

        $config['build'][] = collect($this->getJobBuildings())->random();

        return $config;
    }

    /**
     * Returns unlimited build instructions for buildings that provide jobs.
     *
     * @return array<int, array{land_type: string, building: string, amount: int}>
     */
    protected function getJobBuildings(): array
    {
        return [
            ['land_type' => 'plain', 'building' => 'alchemy', 'amount' => -1],
            ['land_type' => 'plain', 'building' => 'masonry', 'amount' => -1],
            ['land_type' => 'cavern', 'building' => 'school', 'amount' => -1],
            ['land_type' => 'hill', 'building' => 'shrine', 'amount' => -1],
            ['land_type' => 'hill', 'building' => 'factory', 'amount' => -1],
        ];
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
