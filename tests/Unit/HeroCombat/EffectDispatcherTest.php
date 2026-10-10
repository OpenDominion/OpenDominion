<?php

namespace OpenDominion\Tests\Unit\HeroCombat;

use OpenDominion\HeroCombat\Content\AbstractEffect;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\Damage\DamageContext;
use OpenDominion\HeroCombat\Engine\Damage\DamageRequest;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Effects\Hook;
use OpenDominion\Providers\HeroCombatServiceProvider;
use OpenDominion\Tests\Unit\HeroCombat\Support\BattleBuilder;
use OpenDominion\Tests\Unit\HeroCombat\Support\BuildsBattles;
use PHPUnit\Framework\TestCase;

class EffectDispatcherTest extends TestCase
{
    use BuildsBattles;

    /** @var string[] */
    public static array $calls = [];

    protected function setUp(): void
    {
        parent::setUp();
        self::$calls = [];
    }

    private function recorder(string $key, int $priority): AbstractEffect
    {
        return new class($key, $priority) extends AbstractEffect {
            public function __construct(private string $effectKey, private int $priority)
            {
            }

            public function key(): string
            {
                return $this->effectKey;
            }

            public function name(): string
            {
                return $this->effectKey;
            }

            public function handlerPriority(Hook $hook): int
            {
                return $this->priority;
            }

            public function beforeDamageTaken(EffectInstance $instance, DamageContext $damage, Battle $battle): void
            {
                EffectDispatcherTest::$calls[] = $this->effectKey;
            }
        };
    }

    public function testHandlersRunByPriorityThenApplicationOrder(): void
    {
        $registry = HeroCombatServiceProvider::buildRegistry();
        $registry->registerEffect($this->recorder('low_first', 0));
        $registry->registerEffect($this->recorder('high', 10));
        $registry->registerEffect($this->recorder('low_second', 0));
        $registry->registerEffect($this->recorder('team_aura', 5));
        $battle = BattleBuilder::make()->withRegistry($registry)->hero('Hero')->npc('Foe')->build();
        $foe = $this->named($battle, 'Foe');

        $battle->effects->apply($foe, 'low_first');
        $battle->effects->apply($foe, 'high');
        $battle->effects->apply($foe, 'low_second');
        $battle->effects->applyToTeam($foe->team, 'team_aura');

        $battle->damage->resolve(new DamageRequest($this->named($battle, 'Hero'), $foe));

        $this->assertEquals(['high', 'team_aura', 'low_first', 'low_second'], self::$calls);
    }

    public function testShieldAbsorbsBeforeHardinessIsConsulted(): void
    {
        $battle = BattleBuilder::make()->hero('Hero', stats: ['attack' => 125])->npc('Foe')->build();
        $foe = $this->named($battle, 'Foe');
        $battle->effects->apply($foe, 'hardiness');
        $battle->effects->apply($foe, 'shield', null, 1, ['pool' => 20]);

        $battle->damage->resolve(new DamageRequest($this->named($battle, 'Hero'), $foe));

        $this->assertEquals(15, $foe->currentHealth);
        $this->assertTrue($battle->effects->has($foe, 'hardiness'), 'Hardiness is not spent when the shield prevents the lethal hit');
    }

    public function testImmunityBlocksTaggedEffects(): void
    {
        $registry = HeroCombatServiceProvider::buildRegistry();
        $registry->registerEffect(new class extends AbstractEffect {
            public function key(): string
            {
                return 'frost_ward';
            }

            public function name(): string
            {
                return 'Frost Ward';
            }

            public function immuneToTags(): array
            {
                return [\OpenDominion\HeroCombat\Engine\CombatTag::Frost];
            }
        });
        $battle = BattleBuilder::make()->withRegistry($registry)->hero('Hero')->build();
        $hero = $this->named($battle, 'Hero');
        $battle->effects->apply($hero, 'frost_ward');

        $this->assertNull($battle->effects->apply($hero, 'frostbitten'));
        $this->assertFalse($battle->effects->has($hero, 'frostbitten'));
    }
}
