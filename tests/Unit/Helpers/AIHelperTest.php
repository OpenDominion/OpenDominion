<?php

namespace OpenDominion\Tests\Unit\Helpers;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
use OpenDominion\Helpers\AIHelper;
use OpenDominion\Models\Race;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class AIHelperTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var AIHelper */
    protected $aiHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aiHelper = $this->app->make(AIHelper::class);
    }

    protected function tearDown(): void
    {
        global $mockRandomChance;
        $mockRandomChance = null;

        parent::tearDown();
    }

    public function testAttackerUnitPairs(): void
    {
        $orc = Race::where('name', 'Orc')->firstOrFail();

        $this->assertEquals('unit4', $this->aiHelper->getAttackerOffensiveUnit($orc, 'unit2'));
        $this->assertEquals('unit1', $this->aiHelper->getAttackerOffensiveUnit($orc, 'unit3'));
        $this->assertEquals([1, 4], $this->aiHelper->getAttackerOffensiveSlots($orc));
    }

    public function testAttackerOffensiveUnitRejectsUnpairedUnit(): void
    {
        $orc = Race::where('name', 'Orc')->firstOrFail();

        $this->expectException(InvalidArgumentException::class);

        $this->aiHelper->getAttackerOffensiveUnit($orc, 'unit1');
    }

    public function testOrcAttackerConfig(): void
    {
        $orc = Race::where('name', 'Orc')->firstOrFail();

        $config = $this->aiHelper->generateAttackerConfig($orc);

        $this->assertEquals(AIHelper::STRATEGY_ATTACKER, $config['strategy']);
        $this->assertEquals('unit3', $config['military'][0]['unit']);
        $this->assertEquals('unit1', $config['offense']);
        $this->assertEquals(['prestige' => 600, 'military' => 'unit2', 'offense' => 'unit4'], $config['unit_swap']);
        $this->assertEquals(75, $config['min_range']);
        $this->assertEquals(['bloodrage'], $config['attack_spells']);
        $this->assertNotContains('bloodrage', $config['spells']);

        $build = collect($config['build'])->keyBy('building');
        $this->assertEquals(0.18, $build['smithy']['amount']);
        $this->assertEquals('water', $build['dock']['land_type']);
        $this->assertGreaterThanOrEqual(30, $build['dock']['amount']);
        $this->assertLessThanOrEqual(50, $build['dock']['amount']);
        $this->assertFalse($build->has('home'));
        $this->assertEquals(['land_type' => 'hill', 'building' => 'barracks', 'amount' => -1], $build['barracks']);
        $this->assertEquals(0.08, $build['farm']['amount']);
        $this->assertFalse($build->has('ore_mine'));
    }

    public function testSpiritAttackerConfig(): void
    {
        global $mockRandomChance;
        $spirit = Race::where('name', 'Spirit')->firstOrFail();

        $mockRandomChance = true;
        $config = $this->aiHelper->generateAttackerConfig($spirit);
        $this->assertEquals('unit2', $config['military'][0]['unit']);
        $this->assertEquals('unit4', $config['offense']);

        $mockRandomChance = false;
        $config = $this->aiHelper->generateAttackerConfig($spirit);
        $this->assertEquals('unit3', $config['military'][0]['unit']);
        $this->assertEquals('unit1', $config['offense']);

        $this->assertArrayNotHasKey('unit_swap', $config);
        $this->assertEquals(['unholy_ghost'], $config['attack_spells']);
        $this->assertNotContains('unholy_ghost', $config['spells']);

        $build = collect($config['build'])->keyBy('building');
        $this->assertFalse($build->has('dock'));
        $this->assertEquals(0.09, $build['tower']['amount']);
        $this->assertEquals(0.18, $build['smithy']['amount']);
    }

    public function testAttackerConfigRejectsUnsupportedRace(): void
    {
        $human = Race::where('name', 'Human')->firstOrFail();

        $this->assertFalse($this->aiHelper->isAttackerRace($human));
        $this->expectException(InvalidArgumentException::class);

        $this->aiHelper->generateAttackerConfig($human);
    }

    public function testExplorerConfigHasNoStrategy(): void
    {
        $human = Race::where('name', 'Human')->firstOrFail();

        $config = $this->aiHelper->generateConfig($human);

        $this->assertArrayNotHasKey('strategy', $config);
        $this->assertArrayNotHasKey('offense', $config);
    }

    public function testDefenseForNonPlayerAtEarlierTime(): void
    {
        $round = $this->createRound('-7 days');

        $current = $this->aiHelper->getDefenseForNonPlayer($round, 1000);

        $this->assertEquals($current, $this->aiHelper->getDefenseForNonPlayer($round, 1000, now()));
        $this->assertLessThan($current, $this->aiHelper->getDefenseForNonPlayer($round, 1000, now()->subHours(12)));
    }

    public function testAttackerIncomingOffense(): void
    {
        $incoming = $this->aiHelper->getAttackerIncomingOffense();

        $this->assertGreaterThanOrEqual(300, array_sum($incoming));
        $this->assertLessThanOrEqual(350, array_sum($incoming));
        $this->assertGreaterThanOrEqual(2, count($incoming));
        foreach (array_keys($incoming) as $hours) {
            $this->assertGreaterThanOrEqual(4, $hours);
            $this->assertLessThanOrEqual(9, $hours);
        }
    }
}
