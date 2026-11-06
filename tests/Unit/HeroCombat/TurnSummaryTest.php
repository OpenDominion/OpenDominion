<?php

namespace OpenDominion\Tests\Unit\HeroCombat;

use OpenDominion\HeroCombat\Content\Effects\Marked;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\Events\BattleEvent;
use OpenDominion\HeroCombat\Engine\Events\LogEntry;
use OpenDominion\HeroCombat\Presentation\TurnSummary;
use OpenDominion\Providers\HeroCombatServiceProvider;
use OpenDominion\Tests\Unit\HeroCombat\Support\BattleBuilder;
use OpenDominion\Tests\Unit\HeroCombat\Support\BuildsBattles;
use PHPUnit\Framework\TestCase;

class TurnSummaryTest extends TestCase
{
    use BuildsBattles;

    /**
     * Resolves a turn and summarises it with team 1 as the viewer's side.
     */
    protected function summarise(Battle $battle): array
    {
        $turn = $battle->turn();
        $this->resolveTurn($battle);

        $events = [];
        foreach ($battle->log->flush() as $entry) {
            /** @var LogEntry $entry */
            foreach ($entry->events as $event) {
                /** @var BattleEvent $event */
                $events[] = $event->toArray();
            }
        }

        return $this->summary($battle)->build($turn, $events);
    }

    protected function summary(Battle $battle): TurnSummary
    {
        $combatants = [];
        foreach ($battle->combatants() as $combatant) {
            $combatants[$combatant->id] = ['name' => $combatant->name, 'side' => $combatant->team === 1 ? 'ally' : 'enemy', 'human' => $combatant->isPiloted()];
        }

        return new TurnSummary($battle->registry, $combatants);
    }

    /**
     * @return string[] plain text of each line in a phase
     */
    protected function phase(array $summary, string $key): array
    {
        foreach ($summary['phases'] as $phase) {
            if ($phase['key'] === $key) {
                return array_map(fn ($line) => implode('', array_column($line['segments'], 'text')), $phase['lines']);
            }
        }

        return [];
    }

    /**
     * Both sides are queued heroes so every choice is exact.
     */
    protected function duel(array $foeStats = []): Battle
    {
        return BattleBuilder::make()->hero('Hero')->hero('Foe', team: 2, stats: $foeStats)->build();
    }

    public function testDebuffsThatLandShowAsStatusLines(): void
    {
        $battle = $this->duel();
        $this->queue($battle, 'Hero', 'focus');
        $this->queue($battle, 'Foe', 'defend');
        $exposed = $battle->registry->effect('exposed')->name();
        $exposedLines = fn (array $summary) => array_values(array_filter($this->phase($summary, 'status'), fn (string $line) => str_contains($line, $exposed)));
        $this->assertSame([], $exposedLines($this->summarise($battle)), 'A first Defend leaves nothing behind');

        $this->queue($battle, 'Hero', 'focus');
        $this->queue($battle, 'Foe', 'defend');
        $lines = $exposedLines($this->summarise($battle));

        $this->assertCount(1, $lines, 'Listed once');
        $this->assertStringStartsWith('Foe', $lines[0]);
    }

    public function testDamageLinesShowWhatMarkedAdded(): void
    {
        $battle = $this->duel();
        $battle->effects->apply($this->named($battle, 'Foe'), 'marked');
        $battle->evasionRoll = fn () => true;
        $this->queue($battle, 'Hero', 'attack', 'Foe');
        $this->queue($battle, 'Foe', 'focus');

        $line = $this->phase($this->summarise($battle), 'damage')[0];

        $this->assertStringContainsString('+' . Marked::BONUS_DAMAGE . ' ' . $battle->registry->effect('marked')->name(), $line);
        $this->assertStringEndsWith((string) (int) round((20 + Marked::BONUS_DAMAGE) / 2), $line, 'The Mark is added before defense and halved with the rest');
    }

    public function testPhasesFollowTheReadingOrder(): void
    {
        $battle = $this->duel();
        $this->queue($battle, 'Hero', 'attack', 'Foe');
        $this->queue($battle, 'Foe', 'recover');

        $summary = $this->summarise($battle);

        $this->assertEquals(1, $summary['turn']);
        $this->assertEquals(['actions', 'status', 'damage', 'healing'], array_column($summary['phases'], 'key'), 'Recovering leaves Foe Exposed; nobody is defeated');
        $this->assertEquals(['Hero: Attack → Foe', 'Foe: Recover'], $this->phase($summary, 'actions'));
        $this->assertSame([], $this->phase($summary, 'defeats'), 'No Defeats section when nobody falls');
    }

    public function testFocusedAttackOnADefenderShowsItsMaths(): void
    {
        $battle = $this->duel();
        $this->queue($battle, 'Hero', 'focus');
        $this->queue($battle, 'Foe', 'attack', 'Hero');
        $this->summarise($battle);

        $this->queue($battle, 'Hero', 'counter');
        $this->queue($battle, 'Foe', 'defend');
        $first = $this->summarise($battle);

        $this->queue($battle, 'Hero', 'attack', 'Foe');
        $this->queue($battle, 'Foe', 'counter');
        $this->summarise($battle);

        $this->queue($battle, 'Hero', 'focus');
        $this->queue($battle, 'Foe', 'attack', 'Hero');
        $this->summarise($battle);

        $this->queue($battle, 'Hero', 'attack', 'Foe');
        $this->queue($battle, 'Foe', 'defend');
        $second = $this->summarise($battle);

        $this->assertContains("Foe wasn't attacked, so Defend did nothing", $this->phase($first, 'damage'));
        $this->assertEquals(['Hero attacks Foe: 50 (40 +10 focus) − 40 defense (defending) = 10'], $this->phase($second, 'damage'));
    }

    public function testCountersAreShownAndUnusedCountersAreGreyed(): void
    {
        $battle = $this->duel();
        $this->queue($battle, 'Hero', 'attack', 'Foe');
        $this->queue($battle, 'Foe', 'counter');
        $countered = $this->summarise($battle);

        $this->assertEquals([
            'Hero attacks Foe: 40 − 20 defense = 20',
            'Foe counters Hero: 50 (40 +10 counter) − 20 defense = 30',
        ], $this->phase($countered, 'damage'));

        $battle = $this->duel();
        $this->queue($battle, 'Hero', 'defend');
        $this->queue($battle, 'Foe', 'counter');
        $idle = $this->summarise($battle);

        $damage = $idle['phases'][array_search('damage', array_column($idle['phases'], 'key'))];
        $this->assertEquals(["Hero wasn't attacked, so Defend did nothing", "Foe's Counter wasn't triggered"], $this->phase($idle, 'damage'));
        $this->assertTrue($damage['lines'][0]['quiet']);
    }

    public function testGuardingShowsWhoTookTheHitAndLeavesOutRestatedLines(): void
    {
        $battle = BattleBuilder::make()
            ->hero('Tank')
            ->hero('Mage')
            ->hero('Boss', team: 2, stats: ['attack' => 60, 'health' => 500])
            ->build();
        $this->named($battle, 'Tank')->currentHealth = 50;
        $this->queue($battle, 'Tank', 'defend', 'Mage');
        $this->queue($battle, 'Mage', 'recover', 'Tank');
        $this->queue($battle, 'Boss', 'attack', 'Mage');

        $summary = $this->summarise($battle);

        $this->assertContains('Tank: Defend → Mage', $this->phase($summary, 'actions'));
        $this->assertContains('Tank stands guard over Mage.', $this->phase($summary, 'status'));
        $this->assertEquals(['Boss attacks Mage, Tank takes it: 60 − 30 defense (defending) = 30'], $this->phase($summary, 'damage'));
        $this->assertEquals(['Mage heals Tank +20'], $this->phase($summary, 'healing'));
        foreach ($this->phase($summary, 'status') as $line) {
            $this->assertStringNotContainsString('steps in front', $line);
            $this->assertStringNotContainsString('restoring', $line);
        }
    }

    public function testNamesAreColouredBySideIncludingInsideNarration(): void
    {
        $battle = $this->duel();
        $this->queue($battle, 'Hero', 'defend');
        $this->queue($battle, 'Foe', 'attack', 'Hero');

        $summary = $this->summarise($battle);
        $status = $summary['phases'][array_search('status', array_column($summary['phases'], 'key'))];

        $this->assertEquals(
            [['type' => 'name', 'text' => 'Hero', 'side' => 'ally'], ['type' => 'text', 'text' => ' takes a defensive stance.']],
            $status['lines'][0]['segments'],
        );
        $this->assertEquals('<span class="turn-name-enemy">Foe</span> attacks <span class="turn-name-ally">Hero</span>', TurnSummary::html(array_slice(
            $summary['phases'][array_search('damage', array_column($summary['phases'], 'key'))]['lines'][0]['segments'],
            0,
            3,
        )));
    }

    public function testAutomatedChoicesAreNotTagged(): void
    {
        $battle = $this->duel();
        $this->named($battle, 'Hero')->automated = true;
        $this->queue($battle, 'Foe', 'defend');

        $summary = $this->summarise($battle);

        foreach ($summary['phases'][0]['lines'] as $line) {
            $this->assertNotContains('tag', array_column($line['segments'], 'type'));
        }
    }

    public function testDefeatsAndTheGist(): void
    {
        $battle = $this->duel(['health' => 15]);
        $this->queue($battle, 'Hero', 'attack', 'Foe');
        $this->queue($battle, 'Foe', 'focus');

        $summary = $this->summarise($battle);

        $this->assertEquals(['Foe is defeated'], $this->phase($summary, 'defeats'));
        $this->assertEquals('Foe −20 · Foe defeated', implode('', array_column($summary['gist'], 'text')));
    }

    public function testFlatDamageShieldsEvasionRewardsAndOutcomes(): void
    {
        $summary = (new TurnSummary(HeroCombatServiceProvider::buildRegistry(), [
            1 => ['name' => 'Mira', 'side' => 'ally', 'human' => true],
            2 => ['name' => 'Admiral Varos', 'side' => 'enemy', 'human' => false],
            3 => ['name' => 'Deckhand', 'side' => 'enemy', 'human' => false],
        ]))->build(4, [
            ['type' => 'selected', 'actor' => 2, 'ability' => 'broadside', 'target' => 1, 'source' => 'ai'],
            ['type' => 'damage', 'source' => 2, 'target' => 1, 'ability' => 'broadside', 'amount' => 31, 'raw' => 41, 'evaded' => false, 'absorbed' => 10, 'counter' => false, 'redirected_from' => null,
                'flat' => 41, 'attack' => 0, 'focus' => 0, 'counter_bonus' => 0, 'bonus' => 0, 'defense' => 0, 'defending' => false, 'multiplier' => 1.0, 'hits' => 1],
            ['type' => 'damage', 'source' => 1, 'target' => 2, 'ability' => 'attack', 'amount' => 10, 'raw' => 20, 'evaded' => true, 'absorbed' => 0, 'counter' => false, 'redirected_from' => null,
                'flat' => null, 'attack' => 45, 'focus' => 0, 'counter_bonus' => 0, 'bonus' => 0, 'defense' => 25, 'defending' => false, 'multiplier' => 1.0, 'hits' => 1],
            ['type' => 'heal', 'source' => 1, 'target' => 1, 'amount' => 9, 'via' => 'restoring'],
            ['type' => 'summoned', 'source' => 2, 'target' => 3, 'template' => 'deckhand'],
            ['type' => 'message', 'actor' => 2, 'text' => 'Admiral Varos deals 31 damage to Mira.', 'kind' => 'detail'],
            ['type' => 'message', 'actor' => 1, 'text' => 'Mira stands victorious.', 'kind' => 'outcome'],
            ['type' => 'reward', 'target' => 1, 'experience' => 25, 'reason' => 'daily_pvp'],
        ]);

        $this->assertEquals(['Admiral Varos: Broadside → Mira'], $this->phase($summary, 'actions'));
        $this->assertEquals([
            "Admiral Varos's Broadside hits Mira: 41 (ignores defense) = 41, −10 shield → 31",
            'Mira attacks Admiral Varos: 45 − 25 defense = 20, evaded, halved → 10',
        ], $this->phase($summary, 'damage'));
        $this->assertEquals(['Mira recovers +9 (Restoring)'], $this->phase($summary, 'healing'));
        $this->assertEquals(['Admiral Varos summons Deckhand'], $this->phase($summary, 'status'));
        $this->assertEquals(['Mira stands victorious.', 'Mira earns 25 experience'], $this->phase($summary, 'outcome'));
        $this->assertSame([], $this->phase($summary, 'defeats'), 'No Defeats section when nobody falls');
    }

    public function testScaledHitsBracketTheSubtraction(): void
    {
        $summary = (new TurnSummary(HeroCombatServiceProvider::buildRegistry(), [
            1 => ['name' => 'Hero', 'side' => 'ally', 'human' => true],
            2 => ['name' => 'Admiral Varos', 'side' => 'enemy', 'human' => false],
        ]))->build(3, [
            ['type' => 'damage', 'source' => 2, 'target' => 1, 'ability' => 'blade_flurry', 'amount' => 15, 'raw' => 15, 'evaded' => false, 'absorbed' => 0, 'counter' => false, 'redirected_from' => null,
                'flat' => null, 'attack' => 30, 'focus' => 0, 'counter_bonus' => 0, 'bonus' => 0, 'defense' => 20, 'defending' => false, 'multiplier' => 0.75, 'hits' => 2],
        ]);

        $this->assertEquals(["Admiral Varos's Blade Flurry hits Hero: (30 − 20 defense) ×0.75 ×2 hits = 15"], $this->phase($summary, 'damage'));
    }

    public function testAttackValueScalingShowsBeforeTheSubtraction(): void
    {
        $summary = (new TurnSummary(HeroCombatServiceProvider::buildRegistry(), [
            1 => ['name' => 'Hero', 'side' => 'ally', 'human' => true],
            2 => ['name' => 'Admiral Varos', 'side' => 'enemy', 'human' => false],
        ]))->build(3, [
            ['type' => 'damage', 'source' => 1, 'target' => 2, 'ability' => 'blade_flurry', 'amount' => 46, 'raw' => 46, 'evaded' => false, 'absorbed' => 0, 'counter' => false, 'redirected_from' => null,
                'flat' => null, 'attack' => 40, 'focus' => 10, 'counter_bonus' => 0, 'bonus' => 0, 'defense' => 20, 'defending' => false, 'multiplier' => 1.0, 'power_multiplier' => 0.85, 'hits' => 2],
            ['type' => 'damage', 'source' => 2, 'target' => 1, 'ability' => 'blade_flurry', 'amount' => 28, 'raw' => 28, 'evaded' => false, 'absorbed' => 0, 'counter' => false, 'redirected_from' => null,
                'flat' => null, 'attack' => 40, 'focus' => 0, 'counter_bonus' => 0, 'bonus' => 0, 'defense' => 20, 'defending' => false, 'multiplier' => 1.0, 'power_multiplier' => 0.85, 'hits' => 2],
        ]);

        $this->assertEquals([
            "Hero's Blade Flurry hits Admiral Varos: (43 ((40 +10 focus) ×0.85) − 20 defense) ×2 hits = 46",
            "Admiral Varos's Blade Flurry hits Hero: (34 (40 ×0.85) − 20 defense) ×2 hits = 28",
        ], $this->phase($summary, 'damage'));
    }

    public function testHtmlEscapesText(): void
    {
        $this->assertEquals(
            '<span class="turn-name-ally">&lt;b&gt;Hero&lt;/b&gt;</span> &amp; friends',
            TurnSummary::html([['type' => 'name', 'text' => '<b>Hero</b>', 'side' => 'ally'], ['type' => 'text', 'text' => ' & friends']]),
        );
    }
}
