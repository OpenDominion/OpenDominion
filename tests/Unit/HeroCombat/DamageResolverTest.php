<?php

namespace OpenDominion\Tests\Unit\HeroCombat;

use OpenDominion\HeroCombat\Engine\Damage\DamageRequest;
use OpenDominion\Tests\Unit\HeroCombat\Support\BattleBuilder;
use OpenDominion\Tests\Unit\HeroCombat\Support\BuildsBattles;
use OpenDominion\Tests\Unit\HeroCombat\Support\ScriptedRandomSource;
use PHPUnit\Framework\TestCase;

class DamageResolverTest extends TestCase
{
    use BuildsBattles;

    public function testAttackDealsAttackMinusDefense(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->npc('Foe')->build();

        $result = $battle->damage->resolve(new DamageRequest($this->named($battle, 'Hero'), $this->named($battle, 'Foe')));

        $this->assertEquals(20, $result->amount);
        $this->assertEquals(80, $this->named($battle, 'Foe')->currentHealth);
    }

    public function testDefendingDoublesDefensePlusDefendModifier(): void
    {
        $battle = BattleBuilder::make()->hero('Hero', stats: ['attack' => 60])->npc('Foe')->build();
        $foe = $this->named($battle, 'Foe');
        $battle->effects->apply($foe, 'defending', 1);

        $plain = $battle->damage->resolve(new DamageRequest($this->named($battle, 'Hero'), $foe));
        $crushing = $battle->damage->resolve(new DamageRequest($this->named($battle, 'Hero'), $foe, defendModifier: 15));

        $this->assertEquals(20, $plain->amount);
        $this->assertEquals(5, $crushing->amount);
    }

    public function testEvasionHalvesDamage(): void
    {
        $random = new ScriptedRandomSource();
        $random->ints = [0];
        $battle = BattleBuilder::make()->hero('Hero')->npc('Foe', stats: ['evasion' => 50])->build($random);

        $result = $battle->damage->resolve(new DamageRequest($this->named($battle, 'Hero'), $this->named($battle, 'Foe')));

        $this->assertTrue($result->evaded);
        $this->assertEquals(20, $result->raw);
        $this->assertEquals(10, $result->amount);
    }

    public function testElusiveNegatesUnfocusedEvadedHits(): void
    {
        $random = new ScriptedRandomSource();
        $random->ints = [0, 0];
        $battle = BattleBuilder::make()->hero('Hero')->npc('Foe', stats: ['evasion' => 50])->build($random);
        $hero = $this->named($battle, 'Hero');
        $foe = $this->named($battle, 'Foe');
        $battle->effects->apply($foe, 'elusive');

        $unfocused = $battle->damage->resolve(new DamageRequest($hero, $foe));
        $battle->effects->apply($hero, 'focused');
        $focused = $battle->damage->resolve(new DamageRequest($hero, $foe));

        $this->assertEquals(0, $unfocused->amount);
        $this->assertEquals(15, $focused->amount);
    }

    public function testFocusedAttackAddsFocusPerStack(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->npc('Foe')->build();
        $hero = $this->named($battle, 'Hero');
        $battle->effects->apply($hero, 'channeling');
        $battle->effects->apply($hero, 'focused', null, 2);

        $result = $battle->damage->resolve(new DamageRequest($hero, $this->named($battle, 'Foe')));

        $this->assertEquals(40, $result->amount);
    }

    public function testCounteringTargetRetaliatesWithAttackPlusCounter(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->npc('Foe')->build();
        $hero = $this->named($battle, 'Hero');
        $battle->effects->apply($this->named($battle, 'Foe'), 'countering', 1);

        $result = $battle->damage->resolve(new DamageRequest($hero, $this->named($battle, 'Foe'), hits: 2, multiplier: 0.75));

        $this->assertTrue($result->wasCountered());
        $this->assertEquals((40 + 10 - 20) * 2, $result->counterDamage());
        $this->assertEquals(100 - 60, $hero->currentHealth);
    }

    public function testCountersAreNotCounteredOrShielded(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->npc('Foe')->build();
        $hero = $this->named($battle, 'Hero');
        $foe = $this->named($battle, 'Foe');
        $battle->effects->apply($hero, 'countering', 1);
        $battle->effects->apply($hero, 'shield', null, 1, ['pool' => 20]);
        $battle->effects->apply($foe, 'countering', 1);

        $result = $battle->damage->resolve(new DamageRequest($hero, $foe));

        $this->assertNull($result->counter->counter);
        $this->assertEquals(30, $result->counterDamage());
        $this->assertEquals(20, $battle->effects->find($hero, 'shield')->data['pool']);
    }

    public function testShieldAbsorbsUntilDepleted(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->npc('Foe')->build();
        $hero = $this->named($battle, 'Hero');
        $foe = $this->named($battle, 'Foe');
        $battle->effects->apply($foe, 'shield', null, 1, ['pool' => 15]);

        $first = $battle->damage->resolve(new DamageRequest($hero, $foe));
        $second = $battle->damage->resolve(new DamageRequest($hero, $foe));

        $this->assertEquals(5, $first->amount);
        $this->assertEquals(15, $first->absorbed);
        $this->assertFalse($battle->effects->has($foe, 'shield'));
        $this->assertEquals(20, $second->amount);
    }

    public function testHardinessSavesOnceAtOneHealth(): void
    {
        $battle = BattleBuilder::make()->hero('Hero', stats: ['attack' => 200])->npc('Foe')->build();
        $hero = $this->named($battle, 'Hero');
        $foe = $this->named($battle, 'Foe');
        $battle->effects->apply($foe, 'hardiness');

        $battle->damage->resolve(new DamageRequest($hero, $foe));
        $this->assertEquals(1, $foe->currentHealth);

        $battle->damage->resolve(new DamageRequest($hero, $foe));
        $this->assertEquals(0, $foe->currentHealth);
    }

    public function testBypassLethalSaveIgnoresHardiness(): void
    {
        $battle = BattleBuilder::make()->hero('Hero', stats: ['attack' => 200])->npc('Foe')->build();
        $foe = $this->named($battle, 'Foe');
        $battle->effects->apply($foe, 'hardiness');

        $battle->damage->resolve(new DamageRequest($this->named($battle, 'Hero'), $foe, bypassLethalSave: true));

        $this->assertEquals(0, $foe->currentHealth);
    }

    public function testIgnoreDefenseUsesRawAttack(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->npc('Foe', stats: ['defense' => 500])->build();

        $result = $battle->damage->resolve(new DamageRequest($this->named($battle, 'Hero'), $this->named($battle, 'Foe'), multiplier: 0.75, ignoreDefense: true));

        $this->assertEquals(30, $result->amount);
    }

    public function testFlatDamageIgnoresStats(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->npc('Foe', stats: ['defense' => 500])->build();

        $result = $battle->damage->resolve(new DamageRequest($this->named($battle, 'Hero'), $this->named($battle, 'Foe'), flatDamage: 25));

        $this->assertEquals(25, $result->amount);
    }

    public function testCoverRedirectsHitToGuardian(): void
    {
        $battle = BattleBuilder::make()->hero('Tank')->hero('Mage')->npc('Foe', team: 2)->build();
        $tank = $this->named($battle, 'Tank');
        $mage = $this->named($battle, 'Mage');
        $battle->effects->apply($mage, 'covered', 1, 1, [], $tank);

        $result = $battle->damage->resolve(new DamageRequest($this->named($battle, 'Foe'), $mage));

        $this->assertSame($tank, $result->target);
        $this->assertEquals(100, $mage->currentHealth);
        $this->assertEquals(80, $tank->currentHealth);
    }

    public function testLifestealHealsAttacker(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->npc('Foe')->build();
        $hero = $this->named($battle, 'Hero');
        $hero->currentHealth = 50;
        $battle->effects->apply($hero, 'lifesteal');

        $battle->damage->resolve(new DamageRequest($hero, $this->named($battle, 'Foe')));

        $this->assertEquals(60, $hero->currentHealth);
    }
}
