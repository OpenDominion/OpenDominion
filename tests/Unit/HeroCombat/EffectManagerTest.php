<?php

namespace OpenDominion\Tests\Unit\HeroCombat;

use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Stats\Stat;
use OpenDominion\Tests\Unit\HeroCombat\Support\BattleBuilder;
use OpenDominion\Tests\Unit\HeroCombat\Support\BuildsBattles;
use PHPUnit\Framework\TestCase;

class EffectManagerTest extends TestCase
{
    use BuildsBattles;

    public function testStackRuleAddsStacks(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->build();
        $hero = $this->named($battle, 'Hero');

        $battle->effects->apply($hero, 'forged');
        $battle->effects->apply($hero, 'forged');
        $battle->effects->apply($hero, 'forged', null, 2);

        $this->assertCount(1, $hero->effects);
        $this->assertEquals(4, $battle->effects->stacks($hero, 'forged'));
        $this->assertEquals(44, $battle->stat($hero, Stat::Attack));
    }

    public function testStackRuleRespectsMaxStacks(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->build();
        $hero = $this->named($battle, 'Hero');

        $battle->effects->apply($hero, 'focused');
        $battle->effects->apply($hero, 'focused');

        $this->assertEquals(1, $battle->effects->stacks($hero, 'focused'));
    }

    public function testMaxStacksCanDependOnOwner(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->build();
        $hero = $this->named($battle, 'Hero');
        $battle->effects->apply($hero, 'channeling');

        $battle->effects->apply($hero, 'focused');
        $battle->effects->apply($hero, 'focused');

        $this->assertEquals(2, $battle->effects->stacks($hero, 'focused'));
    }

    public function testReplaceRuleSwapsInstance(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->build();
        $hero = $this->named($battle, 'Hero');

        $battle->effects->apply($hero, 'shield', null, 1, ['pool' => 5]);
        $battle->effects->apply($hero, 'shield', null, 1, ['pool' => 20]);

        $this->assertCount(1, $hero->effects);
        $this->assertEquals(20, $battle->effects->find($hero, 'shield')->data['pool']);
    }

    public function testRefreshRuleResetsDuration(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->build();
        $hero = $this->named($battle, 'Hero');

        $battle->effects->apply($hero, 'provoked', 3);
        $battle->effects->tickTurnEnd();
        $battle->effects->apply($hero, 'provoked', 3);

        $this->assertEquals(3, $battle->effects->find($hero, 'provoked')->remainingTurns);
    }

    public function testDurationExpiresAtEndOfFinalTurn(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->build();
        $hero = $this->named($battle, 'Hero');

        $battle->effects->apply($hero, 'provoked', 2);

        $battle->effects->tickTurnEnd();
        $this->assertTrue($battle->effects->has($hero, 'provoked'));

        $battle->effects->tickTurnEnd();
        $this->assertFalse($battle->effects->has($hero, 'provoked'));
    }

    public function testPermanentEffectsNeverExpire(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->build();
        $hero = $this->named($battle, 'Hero');
        $battle->effects->apply($hero, 'frostbitten');

        for ($i = 0; $i < 10; $i++) {
            $battle->effects->tickTurnEnd();
        }

        $this->assertTrue($battle->effects->has($hero, 'frostbitten'));
    }

    public function testRemoveByTagRemovesMatchingEffects(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->build();
        $hero = $this->named($battle, 'Hero');
        $battle->effects->apply($hero, 'frostbitten');

        $removed = $battle->effects->removeByTag($hero, CombatTag::Frost);

        $this->assertEquals(1, $removed);
        $this->assertFalse($battle->effects->has($hero, 'frostbitten'));
    }

    public function testTeamEffectsApplyToTeamMembersExceptExcluded(): void
    {
        $battle = BattleBuilder::make()->npc('Boss')->npc('Book')->hero('Hero')->build();
        $boss = $this->named($battle, 'Boss');
        $book = $this->named($battle, 'Book');
        $hero = $this->named($battle, 'Hero');

        $battle->effects->applyToTeam(2, 'arcane_shield', null, 1, ['exclude' => [$book->id]], $book);

        $this->assertEquals(30, $battle->stat($boss, Stat::Defense));
        $this->assertEquals(20, $battle->stat($book, Stat::Defense));
        $this->assertEquals(20, $battle->stat($hero, Stat::Defense));
    }

    public function testFieldEffectsApplyToEveryone(): void
    {
        $battle = BattleBuilder::make()->npc('Boss')->hero('Hero')->build();

        $battle->effects->applyToField('weakened');

        $this->assertEquals(5, $battle->stat($this->named($battle, 'Boss'), Stat::Defense));
        $this->assertEquals(5, $battle->stat($this->named($battle, 'Hero'), Stat::Defense));
    }

    public function testRemoveBySourceClearsEveryScope(): void
    {
        $battle = BattleBuilder::make()->npc('Book')->hero('Hero')->build();
        $book = $this->named($battle, 'Book');
        $hero = $this->named($battle, 'Hero');

        $battle->effects->applyToTeam(2, 'lifesteal', null, 1, [], $book);
        $battle->effects->apply($hero, 'weakened', null, 1, [], $book);

        $this->assertEquals(2, $battle->effects->removeBySource($book->id));
        $this->assertSame([], $battle->effects->all());
    }

    public function testEffectsRoundTripThroughArrays(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->build();
        $hero = $this->named($battle, 'Hero');
        $instance = $battle->effects->apply($hero, 'shield', 3, 1, ['pool' => 12]);

        $copy = \OpenDominion\HeroCombat\Engine\Effects\EffectInstance::fromArray($instance->toArray());

        $this->assertEquals($instance->key, $copy->key);
        $this->assertEquals(3, $copy->remainingTurns);
        $this->assertEquals(['pool' => 12], $copy->data);
        $this->assertEquals($instance->sequence, $copy->sequence);
    }
}
