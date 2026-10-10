<?php

namespace OpenDominion\Tests\Unit\HeroCombat;

use OpenDominion\HeroCombat\Presentation\BattleView;
use OpenDominion\Models\HeroBattle;
use OpenDominion\Tests\Unit\HeroCombat\Support\BattleBuilder;
use OpenDominion\Tests\Unit\HeroCombat\Support\BuildsBattles;
use PHPUnit\Framework\TestCase;

class BattleViewTest extends TestCase
{
    use BuildsBattles;

    public function testSidesListTheViewersTeamFirstWithTeamNumbers(): void
    {
        $battle = BattleBuilder::make()->npc('Foe', 2)->hero('Player', 1)->build();
        $view = new BattleView(new HeroBattle(), $battle, $this->named($battle, 'Player'));

        $sides = $view->sides();

        $this->assertCount(2, $sides);
        $this->assertEquals(1, $sides[0]['team']);
        $this->assertEquals('Your side', $sides[0]['label']);
        $this->assertEquals(2, $sides[1]['team']);
        $this->assertEquals('Opponents', $sides[1]['label']);
        $this->assertEquals(['Foe'], array_map(fn ($c) => $c->name, $sides[1]['combatants']));
    }

    public function testSidesForSpectatorsKeepTeamOrder(): void
    {
        $battle = BattleBuilder::make()->npc('Foe', 2)->hero('Player', 1)->build();
        $view = new BattleView(new HeroBattle(), $battle, null);

        $this->assertEquals([1, 2], array_column($view->sides(), 'team'));
    }

    public function testSoloHeroCannotQueueAhead(): void
    {
        $battle = BattleBuilder::make()->npc('Foe', 2)->hero('Player', 1)->build();

        $this->assertFalse((new BattleView(new HeroBattle(), $battle, $this->named($battle, 'Player')))->canQueueAhead());
    }

    public function testQueueingAheadNeedsAnotherLivingHuman(): void
    {
        $battle = BattleBuilder::make()->npc('Foe', 2)->hero('Player', 1)->hero('Ally', 1)->build();
        $view = new BattleView(new HeroBattle(), $battle, $this->named($battle, 'Player'));

        $this->assertTrue($view->canQueueAhead());

        $this->named($battle, 'Ally')->currentHealth = 0;
        $this->assertFalse($view->canQueueAhead(), 'A fallen ally can no longer hold up the turn');
    }

    public function testPvpOpponentLetsTheViewerQueueAhead(): void
    {
        $battle = BattleBuilder::make()->hero('Player', 1)->hero('Rival', 2)->build();

        $this->assertTrue((new BattleView(new HeroBattle(), $battle, $this->named($battle, 'Player')))->canQueueAhead());
    }

    public function testSpectatorsCannotQueue(): void
    {
        $battle = BattleBuilder::make()->hero('Player', 1)->hero('Rival', 2)->build();

        $this->assertFalse((new BattleView(new HeroBattle(), $battle, null))->canQueueAhead());
    }
}
