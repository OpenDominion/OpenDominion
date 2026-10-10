<?php

namespace OpenDominion\Tests\Unit\HeroCombat;

use OpenDominion\HeroCombat\Content\Loadouts\HeroClassLoadouts;
use OpenDominion\HeroCombat\Engine\Stats\Stat;
use OpenDominion\Tests\Unit\HeroCombat\Support\BattleBuilder;
use OpenDominion\Tests\Unit\HeroCombat\Support\BuildsBattles;
use OpenDominion\Tests\Unit\HeroCombat\Support\ScriptedRandomSource;
use PHPUnit\Framework\TestCase;

class ClassAbilitiesTest extends TestCase
{
    use BuildsBattles;

    public function testFortifyShieldsTheCasterUpToTwenty(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->npc('Foe')->build();
        $hero = $this->named($battle, 'Hero');
        $battle->effects->apply($hero, 'shield', null, 1, ['pool' => 5]);

        $this->perform($battle, 'Hero', 'fortify');

        $this->assertEquals(20, $battle->effects->find($hero, 'shield')->data['pool']);
    }

    public function testForgeStacksAttack(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->npc('Foe')->build();

        $this->perform($battle, 'Hero', 'forge');
        $this->perform($battle, 'Hero', 'forge');

        $this->assertEquals(42, $battle->stat($this->named($battle, 'Hero'), Stat::Attack));
    }

    public function testTacticalAwarenessHasNoEffectAtTheFloor(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->npc('Foe', stats: ['counter' => 7])->build();
        $foe = $this->named($battle, 'Foe');

        $this->perform($battle, 'Hero', 'tactical_awareness', 'Foe');
        $this->assertEquals(5, $battle->stat($foe, Stat::Counter));

        $text = $this->perform($battle, 'Hero', 'tactical_awareness', 'Foe');
        $this->assertEquals(5, $battle->stat($foe, Stat::Counter));
        $this->assertStringContainsString('has no effect', $text);
    }

    public function testCombatAnalysisLowersDefense(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->npc('Foe')->build();

        $this->perform($battle, 'Hero', 'combat_analysis', 'Foe');

        $this->assertEquals(19, $battle->stat($this->named($battle, 'Foe'), Stat::Defense));
    }

    public function testShadowStrikeCannotBeEvadedAndPunishesDefending(): void
    {
        $random = new ScriptedRandomSource();
        $random->defaultInt = 0;
        $battle = BattleBuilder::make()->hero('Hero', stats: ['attack' => 60])->npc('Foe', stats: ['evasion' => 100])->build($random);
        $this->declare($battle, 'Foe', 'defend');

        $this->perform($battle, 'Hero', 'shadow_strike', 'Foe');

        $this->assertEquals(100 - (60 - 40 + 2), $this->named($battle, 'Foe')->currentHealth);
    }

    public function testVolatileMixtureHitsForOneAndAHalf(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->npc('Foe')->build();

        $this->perform($battle, 'Hero', 'volatile_mixture', 'Foe');

        $this->assertEquals(70, $this->named($battle, 'Foe')->currentHealth);
    }

    public function testVolatileMixtureBackfireStillDrawsTheCounter(): void
    {
        $random = new ScriptedRandomSource();
        $random->chances = [false];
        $battle = BattleBuilder::make()->hero('Hero')->npc('Foe')->build($random);
        $this->declare($battle, 'Foe', 'counter');

        $text = $this->perform($battle, 'Hero', 'volatile_mixture', 'Foe');

        $this->assertEquals(100, $this->named($battle, 'Foe')->currentHealth);
        $this->assertEquals(100 - 20 - 30, $this->named($battle, 'Hero')->currentHealth);
        $this->assertStringContainsString('explodes prematurely', $text);
        $this->assertStringContainsString('distracted alchemist', $text);
    }

    public function testDetonationsHitEveryEnemyThroughDefensesAndShields(): void
    {
        $battle = BattleBuilder::make()
            ->hero('Hero')
            ->npc('Golem A', stats: ['defense' => 100])
            ->npc('Golem B')
            ->build();
        $battle->effects->apply($this->named($battle, 'Golem A'), 'shield', null, 1, ['pool' => 20]);
        $battle->effects->apply($this->named($battle, 'Golem B'), 'hardiness');
        $this->named($battle, 'Golem B')->currentHealth = 10;

        $text = $this->perform($battle, 'Hero', 'demolish');

        $this->assertEquals(70, $this->named($battle, 'Golem A')->currentHealth);
        $this->assertEquals(0, $this->named($battle, 'Golem B')->currentHealth);
        $this->assertStringContainsString('30 damage to all enemies', $text);
    }

    public function testBladeFlurryStrikesTwiceAtReducedDamage(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->npc('Foe')->build();

        $this->perform($battle, 'Hero', 'blade_flurry', 'Foe');

        $this->assertEquals(70, $this->named($battle, 'Foe')->currentHealth);
    }

    public function testCleanseRemovesCurses(): void
    {
        $registry = \OpenDominion\Providers\HeroCombatServiceProvider::buildRegistry();
        $registry->registerEffect(new class extends \OpenDominion\HeroCombat\Content\AbstractEffect {
            public function key(): string
            {
                return 'hex';
            }

            public function name(): string
            {
                return 'Hex';
            }

            public function tags(): array
            {
                return [\OpenDominion\HeroCombat\Engine\CombatTag::Curse];
            }
        });
        $battle = BattleBuilder::make()->withRegistry($registry)->hero('Hero')->build();
        $hero = $this->named($battle, 'Hero');
        $battle->effects->apply($hero, 'hex');
        $battle->effects->apply($hero, 'frostbitten');

        $this->perform($battle, 'Hero', 'cleanse');

        $this->assertFalse($battle->effects->has($hero, 'hex'));
        $this->assertTrue($battle->effects->has($hero, 'frostbitten'), 'Only curses are cleansed');
    }

    public function testMendingRecoverConsumesFocus(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->npc('Foe')->build();
        $hero = $this->named($battle, 'Hero');
        $hero->currentHealth = 50;
        $battle->effects->apply($hero, 'mending');
        $hero->queue[] = ['ability' => 'focus', 'target' => null];
        $this->resolveTurn($battle);
        $healthAfterFirstTurn = $hero->currentHealth;

        $hero->queue[] = ['ability' => 'recover', 'target' => null];
        $this->resolveTurn($battle);

        $this->assertFalse($battle->effects->has($hero, 'focused'));
        $this->assertEquals($healthAfterFirstTurn + 30 - (40 - (20 - 5)), $hero->currentHealth);
    }

    public function testLoadoutsAreOffByDefault(): void
    {
        $loadouts = new HeroClassLoadouts();

        $this->assertEquals(HeroClassLoadouts::BASIC_ABILITIES, $loadouts->abilitiesFor('architect'));
        $this->assertSame([], $loadouts->passivesFor('farmer'));
    }

    public function testEnabledLoadoutsGrantClassAbilities(): void
    {
        $loadouts = new HeroClassLoadouts();

        $this->assertEquals(['attack', 'focus', 'counter', 'recover', 'fortify'], $loadouts->abilitiesFor('architect', true));
        $this->assertContains('shadow_strike', $loadouts->abilitiesFor('infiltrator', true));
        $this->assertEquals(['hardiness'], $loadouts->passivesFor('farmer', true));
    }

    public function testEveryLoadoutKeyIsRegistered(): void
    {
        $registry = \OpenDominion\Providers\HeroCombatServiceProvider::buildRegistry();

        foreach ((new HeroClassLoadouts())->all() as $class => $loadout) {
            foreach ($loadout['abilities'] as $key) {
                $this->assertTrue($registry->hasAbility($key), "{$class} ability {$key}");
            }
            foreach ($loadout['passives'] as $key) {
                $this->assertTrue($registry->hasEffect($key), "{$class} passive {$key}");
            }
        }
    }
}
