<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use Mockery as m;
use Mockery\Mock;
use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\RoundWonder;
use OpenDominion\Models\Wonder;
use OpenDominion\Tests\AbstractBrowserKitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CasualtiesCalculator::class)]
class CasualtiesCalculatorTest extends AbstractBrowserKitTestCase
{
    /** @var Mock|Dominion */
    protected $dominion;

    /** @var Mock|CasualtiesCalculator */
    protected $sut;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dominion = m::mock(Dominion::class);
        $this->target = m::mock(Dominion::class);

        $this->sut = m::mock(CasualtiesCalculator::class, [
            $this->app->make(LandCalculator::class),
            $this->app->make(PopulationCalculator::class),
            $this->app->make(UnitHelper::class),
        ])->makePartial();
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(CasualtiesCalculator::class, $this->app->make(CasualtiesCalculator::class));
    }

    public function testGetStarvationCasualtiesByUnitType()
    {
        $tests = [
            // Enough food: No casualties
            [
                'attributes' => [
                    'resource_food' => 100
                ],
                'expected' => [],
            ],

            // -100 food scenario a
            [
                'attributes' => [
                    'resource_food' => -30,
                    'peasants' => 1300,
                    'military_draftees' => 100,
                    'military_unit1' => 40,
                    'military_unit2' => 160,
                    'military_spies' => 25,
                    'military_wizards' => 25,
                ],
                'expected' => [
                    'peasants' => 15,
                    'military_draftees' => 5,
                    'military_unit1' => 2,
                    'military_unit2' => 8,
                ],
            ],

            // -100 food scenario b
            [
                'attributes' => [
                    'resource_food' => -24,
                    'peasants' => 1300,
                    'military_draftees' => 100,
                ],
                'expected' => [
                    'peasants' => 12,
                    'military_draftees' => 12,
                ],
            ],

            // -100 food scenario c
            [
                'attributes' => [
                    'resource_food' => -1000,
                    'peasants' => 1300,
                    'military_draftees' => 100,
                ],
                'expected' => [
                    'peasants' => 14,
                    'military_draftees' => 14
                ],
            ],
        ];

        $this->dominion->shouldReceive('getAttribute')->with('id')->andReturn($this->dominion->id);

        foreach ($tests as $test) {
            // Set attribute default to 0
            $attributes = [
                'resource_food',
                'peasants',
                'military_draftees',
                'military_unit1',
                'military_unit2',
                'military_unit3',
                'military_unit4',
                'military_spies',
                'military_assassins',
                'military_wizards',
                'military_archmages',
            ];

            foreach ($attributes as $attribute) {
                $this->dominion->shouldReceive('getAttribute')->with($attribute)->andReturn(0)->byDefault();
            }

            foreach ($test['attributes'] as $attribute => $value) {
                $this->dominion->shouldReceive('getAttribute')->with($attribute)->andReturn($value)->byDefault();
            }

            $this->dominion->shouldReceive('getAttribute')->with('queues')->andReturn(new \Illuminate\Database\Eloquent\Collection())->byDefault();
            $this->assertEquals($test['expected'], $this->sut->getStarvationCasualtiesByUnitType($this->dominion, $test['attributes']['resource_food']));
        }
    }

    public function testGetOffensiveCasualtiesMultiplierForUnitSlot()
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('last week');

        $firewalker = $this->createDominion(
            $user,
            $round,
            Race::where('key', 'firewalker')->firstOrFail(),
            $this->createRealm($round, 'good')
        );
        $undead = $this->createDominion(
            $this->createUser(),
            $round,
            Race::where('key', 'undead-rework')->firstOrFail(),
            $this->createRealm($round, 'evil')
        );
        $vampire = $this->createDominion(
            $this->createUser(),
            $round,
            Race::where('key', 'vampire')->firstOrFail(),
            $this->createRealm($round, 'evil')
        );
        $goblin = $this->createDominion(
            $this->createUser(),
            $round,
            Race::where('key', 'goblin')->firstOrFail(),
            $this->createRealm($round, 'evil')
        );

        $wonder = Wonder::where('key', 'high_clerics_tower')->firstOrFail();
        RoundWonder::create([
            'round_id' => $round->id,
            'realm_id' => $goblin->realm_id,
            'wonder_id' => $wonder->id,
            'power' => 1
        ]);

        $undead->military_unit2 = 250;
        $casualtiesCalculator = $this->app->make(CasualtiesCalculator::class);

        $tests = [
            [
                'attributes' => [
                    'attacker' => $firewalker,
                    'slot' => 1,
                    'amount' => 1000,
                    'target' => $undead
                ],
                'expected' => 1
            ],
            [
                'attributes' => [
                    'attacker' => $firewalker,
                    'slot' => 4,
                    'amount' => 1000,
                    'target' => $undead
                ],
                'expected' => 0.5
            ],
            [
                'attributes' => [
                    'attacker' => $firewalker,
                    'slot' => 4,
                    'amount' => 1000,
                    'target' => $goblin
                ],
                'expected' => 1
            ],
            [
                'attributes' => [
                    'attacker' => $vampire,
                    'slot' => 4,
                    'amount' => 1000,
                    'target' => $firewalker
                ],
                'expected' => 0
            ],
            [
                'attributes' => [
                    'attacker' => $vampire,
                    'slot' => 4,
                    'amount' => 1000,
                    'target' => $goblin
                ],
                'expected' => 1
            ],
            [
                'attributes' => [
                    'attacker' => $undead,
                    'slot' => 1,
                    'amount' => 1000,
                    'target' => $firewalker
                ],
                'expected' => 1.2
            ],
            [
                'attributes' => [
                    'attacker' => $undead,
                    'slot' => 1,
                    'amount' => 1000,
                    'target' => $goblin
                ],
                'expected' => 1.2
            ],
            [
                'attributes' => [
                    'attacker' => $undead,
                    'slot' => 4,
                    'amount' => 100,
                    'target' => $firewalker
                ],
                'expected' => 0
            ],
            [
                'attributes' => [
                    'attacker' => $undead,
                    'slot' => 4,
                    'amount' => 1000,
                    'target' => $firewalker
                ],
                'expected' => 0.75
            ],
            [
                'attributes' => [
                    'attacker' => $undead,
                    'slot' => 4,
                    'amount' => 1000,
                    'target' => $goblin
                ],
                'expected' => 1
            ]
        ];

        foreach ($tests as $test) {
            $this->assertEquals(
                $test['expected'],
                $casualtiesCalculator->getOffensiveCasualtiesMultiplierForUnitSlot(
                    $test['attributes']['attacker'],
                    $test['attributes']['target'],
                    $test['attributes']['slot'],
                    [
                        $test['attributes']['slot'] => $test['attributes']['amount']
                    ],
                    0.75
                )
            );
        }
    }
}
