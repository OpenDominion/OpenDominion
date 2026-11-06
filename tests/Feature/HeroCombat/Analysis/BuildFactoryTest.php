<?php

namespace OpenDominion\Tests\Feature\HeroCombat\Analysis;

use InvalidArgumentException;
use OpenDominion\HeroCombat\Analysis\BuildFactory;
use OpenDominion\Tests\AbstractTestCase;

class BuildFactoryTest extends AbstractTestCase
{
    private function factory(): BuildFactory
    {
        return $this->app->make(BuildFactory::class);
    }

    public function testParsesAFullSpec(): void
    {
        $build = $this->factory()->parse('level=12;technique=backstab;ability=shadow_strike;gear=greatsword,hooded_cloak');

        $this->assertEquals(12, $build->level);
        $this->assertEquals('backstab', $build->technique);
        $this->assertEquals('shadow_strike', $build->ability);
        $this->assertEquals(['greatsword', 'hooded_cloak'], $build->gear);
    }

    public function testAbilitiesJoinTheActionBarAndPassivesApply(): void
    {
        $factory = $this->factory();
        $battle = new \OpenDominion\HeroCombat\Engine\Battle(
            new \OpenDominion\HeroCombat\Engine\BattleState(null, 1, 1),
            \OpenDominion\Providers\HeroCombatServiceProvider::buildRegistry(),
            new \OpenDominion\Tests\Unit\HeroCombat\Support\ScriptedRandomSource(),
            new \OpenDominion\HeroCombat\Engine\InMemorySpawner(),
        );

        $fortified = $factory->addTo($battle, $factory->parse('technique=riposte;ability=fortify'), 1, 1, 'A', 'balanced');
        $hardy = $factory->addTo($battle, $factory->parse('technique=hardiness'), 2, 2, 'B', 'balanced');

        $this->assertEquals(['attack', 'defend', 'focus', 'counter', 'recover', 'fortify'], $fortified->abilities);
        $this->assertTrue($battle->effects->has($fortified, 'riposte'));
        $this->assertEquals(['attack', 'defend', 'focus', 'counter', 'recover'], $hardy->abilities);
        $this->assertTrue($battle->effects->has($hardy, 'hardiness'));
    }

    public function testGearAddsToBaseStats(): void
    {
        $factory = $this->factory();

        $basic = $factory->stats($factory->parse('level=9'));
        $geared = $factory->stats($factory->parse('level=9;gear=greatsword'));

        $this->assertEquals($basic['attack'] + 2, $geared['attack']);
        $this->assertEquals($basic['defense'], $geared['defense']);
        $this->assertEquals($basic['evasion'] - 10, $geared['evasion']);
    }

    public function testTwoPiecesOfGearStack(): void
    {
        $factory = $this->factory();

        $basic = $factory->stats($factory->parse('level=12'));
        $geared = $factory->stats($factory->parse('level=12;gear=greatsword,hooded_cloak'));

        $this->assertEquals($basic['attack'] + 2, $geared['attack']);
        $this->assertEquals($basic['evasion'], $geared['evasion'], '-10 and +10');
    }

    public function testRejectsASecondPieceOfGearBeforeLevelSeven(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory()->parse('level=6;gear=greatsword,hooded_cloak');
    }

    public function testRejectsGearThatTakesEvasionBelowZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory()->parse('level=12;gear=greatsword,plate_armor');
    }

    public function testRejectsTheWrongTypeForASlot(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory()->parse('ability=feint');
    }

    public function testRejectsRetiredUpgrades(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory()->parse('technique=overpower');
    }

    public function testBladeFlurryAndWhirlwindAreHeroPicks(): void
    {
        $this->assertEquals('blade_flurry', $this->factory()->parse('ability=blade_flurry')->ability);
        $this->assertEquals('whirlwind', $this->factory()->parse('ability=whirlwind')->ability);
    }

    public function testNpcAbilitiesAreNotHeroPicks(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory()->parse('ability=stone_shield');
    }

    public function testRejectsASecondTechnique(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory()->parse('technique=backstab,riposte');
    }

    public function testRejectsPicksAboveTheHerosLevel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory()->parse('level=6;technique=riposte');
    }

    public function testRejectsAbilitiesBelowTheClassLevel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory()->parse('level=4;ability=fortify');
    }

    public function testRejectsThreePiecesOfGear(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory()->parse('level=12;gear=greatsword,swordbreaker,hooded_cloak');
    }

    public function testRejectsDuplicateGear(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory()->parse('gear=greatsword,greatsword');
    }

    public function testRejectsUnknownOptions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory()->parse('class=wizard');
    }

    public function testListsOptionsByTypeAndLevel(): void
    {
        $this->assertCount(12, $this->factory()->optionsOfType('technique'));
        $this->assertCount(5, $this->factory()->optionsOfType('technique', level: 3));
        $this->assertCount(4, $this->factory()->optionsOfType('technique', level: 7));
        $this->assertCount(8, $this->factory()->optionsOfType('ability'));
        $this->assertCount(0, $this->factory()->optionsOfType('gear', 2));
        $this->assertCount(6, $this->factory()->optionsOfType('gear', 3));
    }

    public function testSimulateCommandReportsAMatchup(): void
    {
        \Illuminate\Support\Facades\Artisan::call('hero-combat:simulate', ['--battles' => 2, '--b' => 'level=9;technique=backstab']);
        $output = \Illuminate\Support\Facades\Artisan::output();

        $this->assertStringContainsString('A score', $output);
        $this->assertStringContainsString('backstab', $output);
    }

    public function testSimulateCommandRunsAGrid(): void
    {
        \Illuminate\Support\Facades\Artisan::call('hero-combat:simulate', ['--grid' => 'abilities', '--battles' => 1, '--strategy-a' => 'balanced', '--strategy-b' => 'balanced']);
        $output = \Illuminate\Support\Facades\Artisan::output();

        $this->assertStringContainsString('shadow_strike', $output);
        $this->assertStringContainsString('avg', $output);
    }

    public function testSimulateCommandRejectsBadSpecs(): void
    {
        \Illuminate\Support\Facades\Artisan::call('hero-combat:simulate', ['--a' => 'technique=nope']);

        $this->assertStringContainsString("'nope' is not a technique", \Illuminate\Support\Facades\Artisan::output());
    }
}
