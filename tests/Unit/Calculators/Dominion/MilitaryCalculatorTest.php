<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use Mockery as m;
use Mockery\Mock;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PrestigeCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Unit;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

/**
 * @coversDefaultClass \OpenDominion\Calculators\Dominion\MilitaryCalculator
 */
class MilitaryCalculatorTest extends AbstractBrowserKitTestCase
{
    /** @var Mock|BuildingCalculator */
    protected $buildingCalculator;

    /** @var Mock|ImprovementCalculator */
    protected $improvementCalculator;

    /** @var Mock|LandCalculator */
    protected $landCalculator;

    /** @var Mock|PrestigeCalculator */
    protected $prestigeCalculator;

    /** @var Mock|QueueService */
    protected $queueService;

    /** @var Mock|SpellCalculator */
    protected $spellCalculator;

    /** @var Mock|MilitaryCalculator */
    protected $sut;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->sut = m::mock(MilitaryCalculator::class, [
            $this->buildingCalculator = m::mock(BuildingCalculator::class),
            $this->improvementCalculator = m::mock(ImprovementCalculator::class),
            $this->landCalculator = m::mock(LandCalculator::class),
            $this->prestigeCalculator = m::mock(PrestigeCalculator::class),
            $this->queueService = m::mock(QueueService::class),
            $this->spellCalculator = m::mock(SpellCalculator::class)
        ])->makePartial();
    }

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $this->assertInstanceOf(MilitaryCalculator::class, $this->app->make(MilitaryCalculator::class));
    }

    /**
     * @covers ::getUnitPowerFromLandBasedPerk
     */
    public function testGetUnitPowerFromLandBasedPerk()
    {
        $tests = [

            // gnome, rockapult, 0% mountains
            [
                'attributes' => [
                    'slot' => '3',
                    'power_type'=>'defense',
                    'perk_value' => ['mountain', '20', '2'],
                    'land' => 250,
                    'land_type' => 'land_mountain',
                    'land_amount' => 0
                ],
                'expected' => 0, // +0 DP
            ],

            // gnome, rockapult, 10% mountains
            [
                'attributes' => [
                    'slot' => '3',
                    'power_type'=>'defense',
                    'perk_value' => ['mountain', '20', '2'],
                    'land' => 250,
                    'land_type' => 'land_mountain',
                    'land_amount' => 25
                ],
                'expected' => 0.5, // +0.5 DP
            ],

            // gnome, rockapult, 30% mountains
            [
                'attributes' => [
                    'slot' => '3',
                    'power_type'=>'defense',
                    'perk_value' => ['mountain', '20', '2'],
                    'land' => 250,
                    'land_type' => 'land_mountain',
                    'land_amount' => 75
                ],
                'expected' => 1.5, // +1.5 DP
            ],

            // gnome, rockapult, 50% mountains
            [
                'attributes' => [
                    'slot' => '3',
                    'power_type'=>'defense',
                    'perk_value' => ['mountain', '20', '2'],
                    'land' => 250,
                    'land_type' => 'land_mountain',
                    'land_amount' => 125
                ],
                'expected' => 2, // +2 is maximum
            ],

        ];

        foreach ($tests as $test) {
            /** @var Mock|Dominion $dominion */
            $dominion = m::mock(Dominion::class);

            /** @var Mock|Unit $unit */
            $unit = m::mock(Unit::class);

            /** @var Mock|Unit $unit */
            $race = m::mock(Race::class);

            $perk_type = $test['attributes']['power_type'] . '_from_land';
            $unit->shouldReceive('getAttribute')->with('slot')->andReturn($test['attributes']['slot'])->byDefault();
            $race->shouldReceive('getUnitPerkValueForUnitSlot')->with($unit->slot, $perk_type, null)->andReturn($test['attributes']['perk_value'])->byDefault();
            $dominion->shouldReceive('getAttribute')->with('race')->andReturn($race)->byDefault();
            $dominion->shouldReceive('getAttribute')->with($test['attributes']['land_type'])->andReturn($test['attributes']['land_amount'])->byDefault();
            $this->landCalculator->shouldReceive('getTotalLand')->with($dominion)->andReturn($test['attributes']['land'])->byDefault();
            $this->buildingCalculator->shouldReceive('getTotalBuildingsForLandType')->with($dominion, 'mountain')->andReturn($test['attributes']['land_amount'])->byDefault();

            $this->assertEquals(
                $test['expected'],
                $this->sut->getUnitPowerFromLandBasedPerk($dominion, $unit, $test['attributes']['power_type']),
                sprintf(
                    "Power Type: %s\nPerk Value: %s\nLand: %s\n%s: %s",
                    $test['attributes']['power_type'],
                    implode($test['attributes']['perk_value'], ','),
                    number_format($test['attributes']['land']),
                    ucwords($test['attributes']['land_type']),
                    number_format($test['attributes']['land_amount'])
                )
            );
        }
    }

    /**
     * @covers ::getUnitPowerFromBuildingBasedPerk
     */
    public function testGetUnitPowerFromBuildingBasedPerk()
    {
        $tests = [

            // dark elf, adept, 0% wizard guilds (offense)
            [
                'attributes' => [
                    'slot' => '3',
                    'power_type'=>'offense',
                    'perk_value' => ['wizard_guild', '10', '5'],
                    'land' => 250,
                    'building_type' => 'building_wizard_guild',
                    'building_amount' => 0
                ],
                'expected' => 0, // +0 OP
            ],

            // dark elf, adept, 10% wizard guilds (offense)
            [
                'attributes' => [
                    'slot' => '3',
                    'power_type'=>'offense',
                    'perk_value' => ['wizard_guild', '10', '5'],
                    'land' => 250,
                    'building_type' => 'building_wizard_guild',
                    'building_amount' => 25
                ],
                'expected' => 1, // +1 OP
            ],

            // dark elf, adept, 30% wizard guilds (offense)
            [
                'attributes' => [
                    'slot' => '3',
                    'power_type'=>'offense',
                    'perk_value' => ['wizard_guild', '10', '5'],
                    'land' => 250,
                    'building_type' => 'building_wizard_guild',
                    'building_amount' => 75
                ],
                'expected' => 3, // +3 OP
            ],

            // dark elf, adept, 60% wizard guilds (offense)
            [
                'attributes' => [
                    'slot' => '3',
                    'power_type'=>'offense',
                    'perk_value' => ['wizard_guild', '10', '5'],
                    'land' => 250,
                    'building_type' => 'building_wizard_guild',
                    'building_amount' => 125
                ],
                'expected' => 5, // +5 is maximum
            ],

            // dark elf, adept, 0% wizard guilds (defense)
            [
                'attributes' => [
                    'slot' => '3',
                    'power_type'=>'defense',
                    'perk_value' => ['wizard_guild', '10', '5'],
                    'land' => 250,
                    'building_type' => 'building_wizard_guild',
                    'building_amount' => 0
                ],
                'expected' => 0, // +0 DP
            ],

            // dark elf, adept, 10% wizard guilds (defense)
            [
                'attributes' => [
                    'slot' => '3',
                    'power_type'=>'defense',
                    'perk_value' => ['wizard_guild', '10', '5'],
                    'land' => 250,
                    'building_type' => 'building_wizard_guild',
                    'building_amount' => 25
                ],
                'expected' => 1, // +1 DP
            ],

            // dark elf, adept, 30% wizard guilds (defense)
            [
                'attributes' => [
                    'slot' => '3',
                    'power_type'=>'defense',
                    'perk_value' => ['wizard_guild', '10', '5'],
                    'land' => 250,
                    'building_type' => 'building_wizard_guild',
                    'building_amount' => 75
                ],
                'expected' => 3, // +3 DP
            ],

            // dark elf, adept, 60% wizard guilds (defense)
            [
                'attributes' => [
                    'slot' => '3',
                    'power_type'=>'defense',
                    'perk_value' => ['wizard_guild', '10', '5'],
                    'land' => 250,
                    'building_type' => 'building_wizard_guild',
                    'building_amount' => 125
                ],
                'expected' => 5, // +5 is maximum
            ],

        ];

        foreach ($tests as $test) {
            /** @var Mock|Dominion $dominion */
            $dominion = m::mock(Dominion::class);

            /** @var Mock|Unit $unit */
            $unit = m::mock(Unit::class);

            /** @var Mock|Unit $unit */
            $race = m::mock(Race::class);

            $perk_type = $test['attributes']['power_type'] . '_from_building';
            $unit->shouldReceive('getAttribute')->with('slot')->andReturn($test['attributes']['slot'])->byDefault();
            $race->shouldReceive('getUnitPerkValueForUnitSlot')->with($unit->slot, $perk_type, null)->andReturn($test['attributes']['perk_value'])->byDefault();
            $dominion->shouldReceive('getAttribute')->with('race')->andReturn($race)->byDefault();
            $dominion->shouldReceive('getAttribute')->with($test['attributes']['building_type'])->andReturn($test['attributes']['building_amount'])->byDefault();
            $this->landCalculator->shouldReceive('getTotalLand')->with($dominion)->andReturn($test['attributes']['land'])->byDefault();

            $this->assertEquals(
                $test['expected'],
                $this->sut->getUnitPowerFromBuildingBasedPerk($dominion, $unit, $test['attributes']['power_type']),
                sprintf(
                    "Power Type: %s\nPerk Value: %s\nBuilding: %s\n%s: %s",
                    $test['attributes']['power_type'],
                    implode($test['attributes']['perk_value'], ','),
                    number_format($test['attributes']['land']),
                    ucwords($test['attributes']['building_type']),
                    number_format($test['attributes']['building_amount'])
                )
            );
        }
    }

    /**
     * @covers ::getUnitPowerFromRawWizardRatioPerk
     */
    public function testGetUnitPowerFromRawWizardRatioPerk()
    {
        $tests = [

            // icekin, ice elemental, 0 wpa
            [
                'attributes' => [
                    'slot' => '3',
                    'power_type'=>'offense',
                    'perk_value' => ['0.85', '3'],
                    'wpa' => 0
                ],
                'expected' => 0, // +0 OP
            ],

            // icekin, ice elemental, 3 wpa
            [
                'attributes' => [
                    'slot' => '3',
                    'power_type'=>'offense',
                    'perk_value' => ['0.85', '3'],
                    'wpa' => 3
                ],
                'expected' => 2.55, // +2.55 OP
            ],

            // icekin, ice elemental, 5 wpa
            [
                'attributes' => [
                    'slot' => '3',
                    'power_type'=>'offense',
                    'perk_value' => ['0.85', '3'],
                    'wpa' => 5
                ],
                'expected' => 3, // +3 is maximum
            ],

        ];

        foreach ($tests as $test) {
            /** @var Mock|Dominion $dominion */
            $dominion = m::mock(Dominion::class);

            /** @var Mock|Unit $unit */
            $unit = m::mock(Unit::class);

            /** @var Mock|Unit $unit */
            $race = m::mock(Race::class);

            $perk_type = $test['attributes']['power_type'] . '_raw_wizard_ratio';
            $unit->shouldReceive('getAttribute')->with('slot')->andReturn($test['attributes']['slot'])->byDefault();
            $race->shouldReceive('getUnitPerkValueForUnitSlot')->with($unit->slot, $perk_type)->andReturn($test['attributes']['perk_value'])->byDefault();
            $dominion->shouldReceive('getAttribute')->with('race')->andReturn($race)->byDefault();
            $this->sut->shouldReceive('getWizardRatioRaw')->with($dominion, $test['attributes']['power_type'])->andReturn($test['attributes']['wpa'])->byDefault();

            $this->assertEquals(
                $test['expected'],
                $this->sut->getUnitPowerFromRawWizardRatioPerk($dominion, $unit, $test['attributes']['power_type']),
                sprintf(
                    "Power Type: %s\nPerk Value: %s\nWPA: %s",
                    $test['attributes']['power_type'],
                    implode($test['attributes']['perk_value'], ','),
                    number_format($test['attributes']['wpa'])
                )
            );
        }
    }

    /**
     * @covers ::getUnitPowerFromStaggeredLandRangePerk
     */
    public function testGetUnitPowerFromStaggeredLandRangePerk()
    {
        $tests = [

            // dark elf, spirit warrior, 50%
            [
                'attributes' => [
                    'slot' => '3',
                    'power_type'=>'offense',
                    'perk_value' => [0 => [75, 0.5], 1 => [95, 1]],
                    'ratio' => 0.5
                ],
                'expected' => 0, // +0 OP
            ],

            // dark elf, spirit warrior, 75%
            [
                'attributes' => [
                    'slot' => '3',
                    'power_type'=>'offense',
                    'perk_value' => [0 => [75, 0.5], 1 => [95, 1]],
                    'ratio' => 0.75
                ],
                'expected' => 0.5, // +0.5 OP
            ],

            // dark elf, spirit warrior, 133%
            [
                'attributes' => [
                    'slot' => '3',
                    'power_type'=>'offense',
                    'perk_value' => [0 => [75, 0.5], 1 => [95, 1]],
                    'ratio' => 1.33
                ],
                'expected' => 1, // +1 OP
            ],

            // gnome, juggernaut, 80%
            [
                'attributes' => [
                    'slot' => '4',
                    'power_type'=>'offense',
                    'perk_value' => [0 => [75, 1], 1 => [85, 2]],
                    'ratio' => 0.8
                ],
                'expected' => 1, // +1 OP
            ],

            // gnome, juggernaut, 133%
            [
                'attributes' => [
                    'slot' => '4',
                    'power_type'=>'offense',
                    'perk_value' => [0 => [75, 1], 1 => [85, 2]],
                    'ratio' => 1.33
                ],
                'expected' => 2, // +2 OP
            ],

        ];

        foreach ($tests as $test) {
            /** @var Mock|Dominion $dominion */
            $dominion = m::mock(Dominion::class);

            /** @var Mock|Unit $unit */
            $unit = m::mock(Unit::class);

            /** @var Mock|Unit $unit */
            $race = m::mock(Race::class);

            $perk_type = $test['attributes']['power_type'] . '_staggered_land_range';
            $unit->shouldReceive('getAttribute')->with('slot')->andReturn($test['attributes']['slot'])->byDefault();
            $race->shouldReceive('getUnitPerkValueForUnitSlot')->with($unit->slot, $perk_type)->andReturn($test['attributes']['perk_value'])->byDefault();
            $dominion->shouldReceive('getAttribute')->with('race')->andReturn($race)->byDefault();

            $this->assertEquals(
                $test['expected'],
                $this->sut->getUnitPowerFromStaggeredLandRangePerk($dominion, $test['attributes']['ratio'], $unit, $test['attributes']['power_type']),
                sprintf(
                    "Power Type: %s\nPerk Value: %s\nLand Ratio: %s",
                    $test['attributes']['power_type'],
                    implode($test['attributes']['perk_value'][0], ';') . ',' . implode($test['attributes']['perk_value'][1], ';'),
                    number_format($test['attributes']['ratio'])
                )
            );
        }
    }

    /**
     * @covers ::getUnitPowerFromVersusRacePerk
     */
    public function testGetUnitPowerFromVersusRacePerk()
    {
        $tests = [

            // troll, basher, vs dwarf
            [
                'attributes' => [
                    'slot' => '3',
                    'power_type'=>'offense',
                    'perk_name' => 'offense_vs_goblin',
                    'perk_value' => 1,
                    'race' => 'dwarf'
                ],
                'expected' => 0, // +0 OP
            ],

            // troll, basher, vs goblin
            [
                'attributes' => [
                    'slot' => '3',
                    'power_type'=>'offense',
                    'perk_name' => 'offense_vs_goblin',
                    'perk_value' => 1,
                    'race' => 'goblin'
                ],
                'expected' => 1, // +1 OP
            ],

            // troll, basher, by goblin
            [
                'attributes' => [
                    'slot' => '3',
                    'power_type'=>'defense',
                    'perk_name' => 'defense_vs_goblin',
                    'perk_value' => 1,
                    'race' => 'goblin'
                ],
                'expected' => 1, // +1 DP
            ],

        ];

        foreach ($tests as $test) {
            /** @var Mock|Dominion $dominion */
            $dominion = m::mock(Dominion::class);

            /** @var Mock|Unit $unit */
            $unit = m::mock(Unit::class);

            /** @var Mock|Unit $unit */
            $race = m::mock(Race::class);

            $perk_type = $test['attributes']['power_type'] . '_vs_' . $test['attributes']['race'];
            $unit->shouldReceive('getAttribute')->with('slot')->andReturn($test['attributes']['slot'])->byDefault();
            $race->shouldReceive('getAttribute')->with('name')->andReturn($test['attributes']['race'])->byDefault();
            $race->shouldReceive('getUnitPerkValueForUnitSlot')->with($unit->slot, $perk_type)->andReturn(0)->byDefault();
            $race->shouldReceive('getUnitPerkValueForUnitSlot')->with($unit->slot, $test['attributes']['perk_name'])->andReturn($test['attributes']['perk_value'])->byDefault();
            $dominion->shouldReceive('getAttribute')->with('race')->andReturn($race)->byDefault();

            $this->assertEquals(
                $test['expected'],
                $this->sut->getUnitPowerFromVersusRacePerk($dominion, $race, $unit, $test['attributes']['power_type']),
                sprintf(
                    "Power Type: %s\nPerk Value: %s\nOpposing Race: %s",
                    $test['attributes']['power_type'],
                    number_format($test['attributes']['perk_value']),
                    $test['attributes']['race']
                )
            );
        }
    }
}
