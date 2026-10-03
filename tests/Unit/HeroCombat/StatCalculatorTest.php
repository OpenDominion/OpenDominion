<?php

namespace OpenDominion\Tests\Unit\HeroCombat;

use OpenDominion\HeroCombat\Engine\Stats\Stat;
use OpenDominion\Tests\Unit\HeroCombat\Support\BattleBuilder;
use OpenDominion\Tests\Unit\HeroCombat\Support\BuildsBattles;
use PHPUnit\Framework\TestCase;

class StatCalculatorTest extends TestCase
{
    use BuildsBattles;

    public function testBaseStatsWithoutEffects(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->build();

        $this->assertEquals(40, $battle->stat($this->named($battle, 'Hero'), Stat::Attack));
        $this->assertEquals(100, $battle->maxHealth($this->named($battle, 'Hero')));
    }

    public function testFlatModifiersApplyBeforePercent(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->build();
        $hero = $this->named($battle, 'Hero');

        $battle->effects->apply($hero, 'frostbitten', null, 4);
        $battle->effects->apply($hero, 'defending', 1);

        $this->assertEquals((20 - 4) * 2, $battle->stat($hero, Stat::Defense));
    }

    public function testStatsNeverGoNegative(): void
    {
        $battle = BattleBuilder::make()->hero('Hero', stats: ['defense' => 10])->build();
        $hero = $this->named($battle, 'Hero');

        $battle->effects->apply($hero, 'weakened');

        $this->assertEquals(0, $battle->stat($hero, Stat::Defense));
    }

    public function testLowHealthPassivesOnlyApplyAtOrBelowThreshold(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->build();
        $hero = $this->named($battle, 'Hero');
        $battle->effects->apply($hero, 'enrage');

        $this->assertEquals(40, $battle->stat($hero, Stat::Attack));

        $hero->currentHealth = 40;
        $this->assertEquals(50, $battle->stat($hero, Stat::Attack));
    }

    public function testMendingAddsBaseFocusToRecoverWhileFocused(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->build();
        $hero = $this->named($battle, 'Hero');
        $battle->effects->apply($hero, 'mending');

        $this->assertEquals(20, $battle->stat($hero, Stat::Recover));

        $battle->effects->apply($hero, 'focused');
        $this->assertEquals(30, $battle->stat($hero, Stat::Recover));
    }
}
