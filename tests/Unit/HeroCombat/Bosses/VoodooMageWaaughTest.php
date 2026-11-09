<?php

namespace OpenDominion\Tests\Unit\HeroCombat\Bosses;

use OpenDominion\HeroCombat\Content\Abilities\Boss\BoneSplinter;
use OpenDominion\HeroCombat\Content\Abilities\Boss\Hexfire;
use OpenDominion\HeroCombat\Content\Effects\StolenVigor;
use OpenDominion\HeroCombat\Content\Effects\Confused;
use OpenDominion\HeroCombat\Content\Effects\Passives\ClearMinded;
use OpenDominion\HeroCombat\Content\Encounters\VoodooMageWaaughEncounter;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Effects\Hook;
use OpenDominion\HeroCombat\Engine\Events\EventType;
use OpenDominion\HeroCombat\Engine\Intent;
use OpenDominion\Tests\Unit\HeroCombat\Support\BattleBuilder;
use OpenDominion\Tests\Unit\HeroCombat\Support\BuildsBattles;
use OpenDominion\Tests\Unit\HeroCombat\Support\ScriptedRandomSource;
use PHPUnit\Framework\TestCase;

/**
 * Voodoo Mage Waaugh: Dragon's Miasma confuses unfocused heroes, Bone Splinter punishes them.
 */
class VoodooMageWaaughTest extends TestCase
{
    use BuildsBattles;

    protected Battle $battle;

    protected ScriptedRandomSource $random;

    protected function setUp(): void
    {
        parent::setUp();
        $this->random = new ScriptedRandomSource();
        $this->battle = BattleBuilder::make('voodoo_mage_waaugh')->withRoster()->hero('Player')->build($this->random);
    }

    protected function waaugh(): CombatantState
    {
        return $this->named($this->battle, 'Voodoo Mage Waaugh');
    }

    protected function player(): CombatantState
    {
        return $this->named($this->battle, 'Player');
    }

    /**
     * Runs the end of the given turn: TurnEnd hooks, then duration ticking.
     */
    protected function endTurn(int $turn): void
    {
        $this->battle->state->turn = $turn;
        $this->battle->dispatcher->runEverywhere(Hook::TurnEnd, $this->battle);
        $this->battle->effects->tickTurnEnd();
    }

    protected function confuse(CombatantState $hero, float $chance = Confused::DEFAULT_CHANCE): void
    {
        $this->battle->effects->apply($hero, 'confused', null, 1, ['chance' => $chance]);
    }

    protected function selectedEvent(): array
    {
        foreach ($this->battle->log->entries() as $entry) {
            foreach ($entry->events as $event) {
                if ($event->type === EventType::Selected && $event->payload['actor'] === $this->player()->id) {
                    return $event->payload;
                }
            }
        }

        $this->fail('No selected event for the player');
    }

    // Dragon's Miasma

    public function testMiasmaGathersThenConfusesAnUnfocusedHero(): void
    {
        $miasma = $this->battle->effects->find($this->waaugh(), 'dragons_miasma');

        $this->endTurn(1);
        $this->assertTrue($miasma->data['gathering']);

        $this->endTurn(2);
        $this->assertArrayNotHasKey('gathering', $miasma->data);
        $this->assertTrue($this->battle->hasTag($this->player(), CombatTag::Confused));
    }

    public function testMiasmaDoesNothingWithoutGatheringFirst(): void
    {
        $this->endTurn(2);

        $this->assertFalse($this->battle->hasTag($this->player(), CombatTag::Confused));
    }

    public function testConfusionLastsTheTwoTurnsAfterTheExhale(): void
    {
        $this->endTurn(1);
        $this->endTurn(2);

        $this->endTurn(3);
        $this->assertTrue($this->battle->hasTag($this->player(), CombatTag::Confused), 'Still confused during turn 4');

        $this->endTurn(4);
        $this->assertFalse($this->battle->hasTag($this->player(), CombatTag::Confused));
        $this->assertStringContainsString('The fog lifts', $this->battle->log->text());
    }

    public function testFocusedHeroResists(): void
    {
        $this->endTurn(1);
        $this->battle->effects->apply($this->player(), 'focused');
        $this->endTurn(2);

        $this->assertFalse($this->battle->hasTag($this->player(), CombatTag::Confused));
    }

    public function testEachHeroResistsOrIsConfusedIndividually(): void
    {
        $battle = BattleBuilder::make('voodoo_mage_waaugh')->withRoster()->hero('Alice')->hero('Bob')->build();
        $waaugh = $this->named($battle, 'Voodoo Mage Waaugh');
        $alice = $this->named($battle, 'Alice');
        $bob = $this->named($battle, 'Bob');
        $battle->effects->apply($alice, 'focused');

        foreach ([1, 2] as $turn) {
            $battle->state->turn = $turn;
            $battle->dispatcher->runEverywhere(Hook::TurnEnd, $battle);
        }

        $this->assertFalse($battle->hasTag($alice, CombatTag::Confused));
        $this->assertTrue($battle->hasTag($bob, CombatTag::Confused));
        $this->assertEquals($bob->id, $battle->dispatcher->first(Hook::ForcedIntent, [$waaugh], $waaugh, $battle)->targetId);
    }

    public function testClearMindedHeroesAreConfusedLessOften(): void
    {
        $this->battle->effects->apply($this->player(), 'clear_minded');

        $this->endTurn(1);
        $this->endTurn(2);

        $confused = $this->battle->effects->find($this->player(), 'confused');
        $this->assertEquals(ClearMinded::CONFUSION_CHANCE, Confused::chance($confused));
    }

    // Bone Splinter and Hexfire

    public function testWaaughCastsBoneSplinterAtAConfusedHero(): void
    {
        $this->assertNull($this->forcedIntent($this->waaugh()));

        $this->confuse($this->player());
        $intent = $this->forcedIntent($this->waaugh());

        $this->assertEquals('bone_splinter', $intent->abilityKey);
        $this->assertEquals($this->player()->id, $intent->targetId);
    }

    public function testBoneSplinterIgnoresDefendingAndFeedsWaaugh(): void
    {
        $this->declare($this->battle, 'Player', 'defend');

        $this->perform($this->battle, 'Voodoo Mage Waaugh', 'bone_splinter', 'Player');
        $this->assertEquals(100 - BoneSplinter::DAMAGE, $this->player()->currentHealth);
        $this->assertEquals(1, $this->battle->effects->stacks($this->waaugh(), 'stolen_vigor'));

        $this->perform($this->battle, 'Voodoo Mage Waaugh', 'bone_splinter', 'Player');
        $this->assertEquals(100 - BoneSplinter::DAMAGE * 2 - StolenVigor::DAMAGE_PER_STACK, $this->player()->currentHealth);
        $this->assertEquals(2, $this->battle->effects->stacks($this->waaugh(), 'stolen_vigor'));
    }

    public function testHexfireGrowsWithStolenVigor(): void
    {
        $this->perform($this->battle, 'Voodoo Mage Waaugh', 'hexfire', 'Player');
        $this->assertEquals(100 - Hexfire::DAMAGE, $this->player()->currentHealth);

        $this->battle->effects->apply($this->waaugh(), 'stolen_vigor', null, 2);
        $this->perform($this->battle, 'Voodoo Mage Waaugh', 'hexfire', 'Player');
        $this->assertEquals(100 - Hexfire::DAMAGE * 2 - StolenVigor::DAMAGE_PER_STACK * 2, $this->player()->currentHealth);
    }

    // Confused

    public function testConfusionCanReplaceAQueuedAction(): void
    {
        $this->confuse($this->player());
        $this->queue($this->battle, 'Player', 'attack');

        $this->resolveTurn($this->battle);

        $this->assertEquals('defend', $this->player()->lastAction);
        $this->assertSame([], $this->player()->queue, 'The replaced order is used up');
        $event = $this->selectedEvent();
        $this->assertEquals(Intent::SOURCE_ALTERED, $event['source']);
        $this->assertEquals(['ability' => 'attack', 'target' => null, 'source' => Intent::SOURCE_QUEUE], $event['replaces']);
    }

    public function testConfusionRollCanFail(): void
    {
        $this->confuse($this->player());
        $this->queue($this->battle, 'Player', 'attack');
        $this->random->chances = [false];

        $this->resolveTurn($this->battle);

        $this->assertEquals('attack', $this->player()->lastAction);
        $this->assertEquals(Intent::SOURCE_QUEUE, $this->selectedEvent()['source']);
    }

    public function testConfusionNeverPicksTheChosenAbility(): void
    {
        $this->confuse($this->player());
        $intent = new Intent($this->player()->id, 'defend', null, Intent::SOURCE_QUEUE);

        $altered = $this->alterIntent($intent);

        $this->assertEquals('recover', $altered->abilityKey);
    }

    public function testConfusionRespectsCooldowns(): void
    {
        $this->confuse($this->player());
        $this->player()->cooldowns['recover'] = $this->battle->turn() + 1;
        $intent = new Intent($this->player()->id, 'defend', null, Intent::SOURCE_QUEUE);

        $this->assertEquals('counter', $this->alterIntent($intent)->abilityKey);
    }

    public function testConfusionNeverPicksFocus(): void
    {
        $battle = BattleBuilder::make('voodoo_mage_waaugh')->withRoster()->hero('Player', abilities: ['attack', 'focus'])->build();
        $player = $this->named($battle, 'Player');
        $battle->effects->apply($player, 'confused');

        $altered = $battle->dispatcher->first(Hook::AlterIntent, [$player], new Intent($player->id, 'attack'), $player, $battle);

        $this->assertNull($altered);
    }

    public function testConfusionNeverAltersForcedActions(): void
    {
        $this->confuse($this->player());
        $intent = new Intent($this->player()->id, 'attack', null, Intent::SOURCE_FORCED);

        $this->assertNull($this->alterIntent($intent));
    }

    // Encounter

    public function testScholarsAndSorcerersAreClearMinded(): void
    {
        $encounter = new VoodooMageWaaughEncounter();

        $this->assertEquals(['clear_minded' => []], $encounter->playerEffects($this->player(), ['scholar']));
        $this->assertEquals(['clear_minded' => []], $encounter->playerEffects($this->player(), ['blacksmith', 'sorcerer']));
        $this->assertEquals([], $encounter->playerEffects($this->player(), ['blacksmith']));
    }

    public function testFocusingOnTheExhaleTurnAvoidsBoneSplinter(): void
    {
        $this->queue($this->battle, 'Player', 'defend');
        $this->resolveTurn($this->battle);
        $this->queue($this->battle, 'Player', 'focus');
        $this->resolveTurn($this->battle);

        $this->assertFalse($this->battle->hasTag($this->player(), CombatTag::Confused));

        $this->queue($this->battle, 'Player', 'attack');
        $this->resolveTurn($this->battle);

        $this->assertNotEquals('bone_splinter', $this->waaugh()->lastAction);
        $this->assertEquals(0, $this->battle->effects->stacks($this->waaugh(), 'stolen_vigor'));
    }

    protected function forcedIntent(CombatantState $combatant): ?Intent
    {
        return $this->battle->dispatcher->first(Hook::ForcedIntent, [$combatant], $combatant, $this->battle);
    }

    protected function alterIntent(Intent $intent): ?Intent
    {
        return $this->battle->dispatcher->first(Hook::AlterIntent, [$this->player()], $intent, $this->player(), $this->battle);
    }
}
