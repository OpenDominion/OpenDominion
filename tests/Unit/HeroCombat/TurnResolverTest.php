<?php

namespace OpenDominion\Tests\Unit\HeroCombat;

use OpenDominion\HeroCombat\Engine\ActionValidator;
use OpenDominion\HeroCombat\Engine\Events\EventType;
use OpenDominion\HeroCombat\Engine\Random\SeededRandomSource;
use OpenDominion\Tests\Unit\HeroCombat\Support\BattleBuilder;
use OpenDominion\Tests\Unit\HeroCombat\Support\BuildsBattles;
use PHPUnit\Framework\TestCase;

class TurnResolverTest extends TestCase
{
    use BuildsBattles;

    public function testHumanWithoutQueuedActionIsNotReady(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->npc('Foe')->build();

        $this->assertFalse($this->engine()->isReady($battle));
        $this->assertEquals(0, $this->engine()->advance($battle));

        $this->queue($battle, 'Hero', 'attack', 'Foe');
        $this->assertTrue($this->engine()->isReady($battle));
    }

    public function testAdvanceResolvesQueuedTurnsThenStops(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->npc('Foe')->build();
        $this->queue($battle, 'Hero', 'attack', 'Foe');
        $this->queue($battle, 'Hero', 'defend');

        $turns = $this->engine()->advance($battle);

        $this->assertEquals(2, $turns);
        $this->assertEquals(3, $battle->turn());
        $this->assertEquals(80, $this->named($battle, 'Foe')->currentHealth);
        $this->assertEquals(80, $this->named($battle, 'Hero')->currentHealth);
    }

    public function testStancesApplyRegardlessOfResolutionOrder(): void
    {
        $battle = BattleBuilder::make()->npc('Foe', team: 2)->hero('Hero', team: 1)->build();
        $this->queue($battle, 'Hero', 'defend');

        $this->resolveTurn($battle);

        $this->assertEquals(100, $this->named($battle, 'Hero')->currentHealth);
    }

    public function testHigherPriorityResolvesFirst(): void
    {
        $battle = BattleBuilder::make()
            ->npc('Foe', team: 2)
            ->hero('Hero', team: 1, abilities: ['attack', 'fortify'])
            ->build();
        $this->queue($battle, 'Hero', 'fortify');

        $this->resolveTurn($battle);

        $this->assertEquals(100, $this->named($battle, 'Hero')->currentHealth);
        $this->assertFalse($battle->effects->has($this->named($battle, 'Hero'), 'shield'));
    }

    public function testCombatantDownedThisTurnStillActs(): void
    {
        $battle = BattleBuilder::make()
            ->hero('Hero', stats: ['attack' => 200])
            ->npc('Foe')
            ->build();
        $this->queue($battle, 'Hero', 'attack', 'Foe');

        $this->resolveTurn($battle);

        $this->assertEquals(0, $this->named($battle, 'Foe')->currentHealth);
        $this->assertEquals(80, $this->named($battle, 'Hero')->currentHealth);
        $this->assertTrue($battle->state->finished);
        $this->assertEquals(1, $battle->state->winningTeam);
    }

    public function testDeadTargetIsReplacedByAnotherEnemy(): void
    {
        $battle = BattleBuilder::make()
            ->hero('Hero')
            ->npc('Dead')
            ->npc('Alive')
            ->build();
        $this->named($battle, 'Dead')->currentHealth = 0;
        $this->named($battle, 'Dead')->deathProcessed = true;
        $this->queue($battle, 'Hero', 'attack', 'Dead');

        $this->resolveTurn($battle);

        $this->assertEquals(80, $this->named($battle, 'Alive')->currentHealth);
    }

    public function testProvokedCombatantMustTargetProvoker(): void
    {
        $battle = BattleBuilder::make()
            ->hero('Hero')
            ->npc('Minion')
            ->npc('Boss')
            ->build();
        $hero = $this->named($battle, 'Hero');
        $battle->effects->apply($hero, 'provoked', 1, 1, [], $this->named($battle, 'Boss'));
        $this->queue($battle, 'Hero', 'attack', 'Minion');

        $this->resolveTurn($battle);

        $this->assertEquals(100, $this->named($battle, 'Minion')->currentHealth);
        $this->assertEquals(80, $this->named($battle, 'Boss')->currentHealth);
    }

    public function testCooldownBlocksConsecutiveUse(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->npc('Foe')->build();
        $hero = $this->named($battle, 'Hero');
        $validator = new ActionValidator($battle);

        $this->queue($battle, 'Hero', 'counter');
        $this->resolveTurn($battle);

        $this->assertFalse($validator->canPerform($hero, 'counter'));
        $this->assertEquals(1, $validator->turnsUntilReady($hero, 'counter'));

        $this->queue($battle, 'Hero', 'attack', 'Foe');
        $this->resolveTurn($battle);

        $this->assertTrue($validator->canPerform($hero, 'counter'));
    }

    public function testQueueValidationProjectsCooldownsForward(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->npc('Foe')->build();
        $hero = $this->named($battle, 'Hero');
        $validator = new ActionValidator($battle);

        $this->queue($battle, 'Hero', 'counter');
        $this->assertFalse($validator->canQueue($hero, 'counter'));

        $this->queue($battle, 'Hero', 'attack', 'Foe');
        $this->assertTrue($validator->canQueue($hero, 'counter'));
    }

    public function testInvalidQueuedActionFallsBackToStrategy(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->npc('Foe')->build();
        $hero = $this->named($battle, 'Hero');
        $hero->ai = 'attack';
        $hero->cooldowns['counter'] = 5;
        $this->queue($battle, 'Hero', 'counter');

        $this->resolveTurn($battle);

        $this->assertEquals('attack', $hero->lastAction);
    }

    public function testChargesLimitUsesPerBattle(): void
    {
        $registry = \OpenDominion\Providers\HeroCombatServiceProvider::buildRegistry();
        $registry->registerAbility(new class extends \OpenDominion\HeroCombat\Content\Abilities\Attack {
            public function key(): string
            {
                return 'limited_strike';
            }

            public function maxCharges(\OpenDominion\HeroCombat\Engine\CombatantState $actor, \OpenDominion\HeroCombat\Engine\Battle $battle): ?int
            {
                return 1;
            }
        });
        $battle = BattleBuilder::make()->withRegistry($registry)
            ->hero('Hero', abilities: ['attack', 'limited_strike'])
            ->npc('Foe')
            ->build();
        $hero = $this->named($battle, 'Hero');
        $validator = new ActionValidator($battle);

        $this->assertEquals(1, $validator->chargesRemaining($hero, 'limited_strike'));
        $this->queue($battle, 'Hero', 'limited_strike', 'Foe');
        $this->resolveTurn($battle);

        $this->assertEquals(0, $validator->chargesRemaining($hero, 'limited_strike'));
        $this->assertFalse($validator->canPerform($hero, 'limited_strike'));
    }

    public function testStunnedCombatantPasses(): void
    {
        $registry = \OpenDominion\Providers\HeroCombatServiceProvider::buildRegistry();
        $registry->registerEffect(new class extends \OpenDominion\HeroCombat\Content\AbstractEffect {
            public function key(): string
            {
                return 'stunned';
            }

            public function name(): string
            {
                return 'Stunned';
            }

            public function tags(): array
            {
                return [\OpenDominion\HeroCombat\Engine\CombatTag::Stunned];
            }
        });
        $battle = BattleBuilder::make()->withRegistry($registry)->hero('Hero')->npc('Foe')->build();
        $battle->effects->apply($this->named($battle, 'Foe'), 'stunned', 1);
        $this->queue($battle, 'Hero', 'defend');

        $this->resolveTurn($battle);

        $this->assertEquals('pass', $this->named($battle, 'Foe')->lastAction);
        $this->assertFalse($battle->effects->has($this->named($battle, 'Foe'), 'stunned'));
    }

    public function testTwoHeroesCooperateAgainstBoss(): void
    {
        $battle = BattleBuilder::make()
            ->hero('Alice', team: 1)
            ->hero('Bob', team: 1)
            ->npc('Boss', team: 2, stats: ['health' => 60])
            ->build();

        $this->queue($battle, 'Alice', 'attack', 'Boss');
        $this->assertFalse($this->engine()->isReady($battle), 'Waits for every human on the team');

        $this->queue($battle, 'Bob', 'attack', 'Boss');
        $this->engine()->advance($battle);

        $this->assertEquals(20, $this->named($battle, 'Boss')->currentHealth);
        $this->assertFalse($battle->state->finished);

        $this->queue($battle, 'Alice', 'attack', 'Boss');
        $this->queue($battle, 'Bob', 'attack', 'Boss');
        $this->engine()->advance($battle);

        $this->assertTrue($battle->state->finished);
        $this->assertEquals(1, $battle->state->winningTeam);
    }

    public function testHeroesCannotTargetTheirOwnTeamWithHostileAbilities(): void
    {
        $battle = BattleBuilder::make()->hero('Alice', team: 1)->hero('Bob', team: 1)->npc('Boss', team: 2)->build();
        $this->queue($battle, 'Alice', 'attack', 'Bob');
        $this->queue($battle, 'Bob', 'defend');

        $this->resolveTurn($battle);

        $this->assertEquals(100, $this->named($battle, 'Bob')->currentHealth);
        $this->assertEquals(80, $this->named($battle, 'Boss')->currentHealth);
    }

    public function testTeamPvpWinnerIsTheSurvivingTeam(): void
    {
        $battle = BattleBuilder::make()
            ->hero('A1', team: 1, stats: ['attack' => 200])
            ->hero('A2', team: 1, stats: ['attack' => 200])
            ->hero('B1', team: 2)
            ->hero('B2', team: 2)
            ->build();
        $this->queue($battle, 'A1', 'attack', 'B1');
        $this->queue($battle, 'A2', 'attack', 'B2');
        $this->queue($battle, 'B1', 'defend');
        $this->queue($battle, 'B2', 'defend');

        $this->engine()->advance($battle);

        $this->assertTrue($battle->state->finished);
        $this->assertEquals(1, $battle->state->winningTeam);
    }

    public function testNpcAllyFightsForPlayersTeam(): void
    {
        $battle = BattleBuilder::make()
            ->hero('Hero', team: 1)
            ->npc('Squire', team: 1)
            ->npc('Foe', team: 2, stats: ['health' => 200])
            ->build();
        $this->queue($battle, 'Hero', 'defend');

        $this->resolveTurn($battle);

        $this->assertEquals(180, $this->named($battle, 'Foe')->currentHealth, 'The squire attacks the enemy team');
        $this->assertEquals(100, $this->named($battle, 'Hero')->currentHealth, 'The foe attacks the defending hero, not its own side');
    }

    public function testMutualKnockoutIsADraw(): void
    {
        $battle = BattleBuilder::make()
            ->hero('Hero', stats: ['attack' => 200])
            ->npc('Foe', stats: ['attack' => 200])
            ->build();
        $this->queue($battle, 'Hero', 'attack', 'Foe');

        $this->resolveTurn($battle);

        $this->assertTrue($battle->state->finished);
        $this->assertNull($battle->state->winningTeam);
    }

    public function testFocusIsConsumedByTheNextAttack(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->npc('Foe', stats: ['health' => 500], ai: 'summoner')->build();
        $hero = $this->named($battle, 'Hero');
        $this->queue($battle, 'Hero', 'focus');
        $this->queue($battle, 'Hero', 'attack', 'Foe');
        $this->queue($battle, 'Hero', 'attack', 'Foe');

        $this->resolveTurn($battle);
        $this->assertTrue($battle->effects->has($hero, 'focused'));

        $foe = $this->named($battle, 'Foe');
        $foe->ai = 'attack';
        $healthBefore = $foe->currentHealth;
        $this->resolveTurn($battle);
        $this->assertEquals(30, $healthBefore - $foe->currentHealth);
        $this->assertFalse($battle->effects->has($hero, 'focused'));

        $healthBefore = $foe->currentHealth;
        $this->resolveTurn($battle);
        $this->assertEquals(20, $healthBefore - $foe->currentHealth);
    }

    public function testLogRecordsActionsWithStructuredEvents(): void
    {
        $battle = BattleBuilder::make()->hero('Hero')->npc('Foe')->build();
        $this->queue($battle, 'Hero', 'attack', 'Foe');

        $this->resolveTurn($battle);

        $entries = $battle->log->entries();
        $heroEntry = collect($entries)->first(fn ($e) => $e->actorId === $this->named($battle, 'Hero')->id);
        $this->assertEquals('attack', $heroEntry->action);
        $this->assertEquals(20, $heroEntry->damage);
        $this->assertStringContainsString('Hero deals 20 damage to Foe.', $heroEntry->description());
        $this->assertEquals(EventType::Selected, $heroEntry->events[0]->type);
        $this->assertEquals('queue', $heroEntry->events[0]->payload['source']);
        $this->assertEquals(EventType::Damage, $heroEntry->events[1]->type);
    }

    public function testSeededBattlesAreDeterministic(): void
    {
        $run = function (): array {
            $battle = BattleBuilder::make()
                ->npc('A', team: 1, stats: ['evasion' => 30], ai: 'balanced')
                ->npc('B', team: 2, stats: ['evasion' => 30], ai: 'aggressive')
                ->build();
            $this->engine()->advance($battle, function ($battle) {
                $battle->random = SeededRandomSource::forTurn(1234, $battle->turn());
            });

            return [$battle->turn(), $battle->state->winningTeam, $battle->log->text()];
        };

        $this->assertEquals($run(), $run());
    }
}
