<?php

namespace OpenDominion\Tests\Feature\HeroCombat;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hero;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\Actions\HeroActionService;
use OpenDominion\Services\Dominion\HeroBattleService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

/**
 * Evil Twin (2v2): the pilot's hero and a realmmate's hero face an Evil Twin of each.
 */
class EvilTwinPairTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    protected Round $round;

    protected Realm $realm;

    protected HeroBattleService $heroBattleService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->round = $this->createRound('-3 days midnight');
        $this->realm = $this->createRealm($this->round);
        $this->heroBattleService = $this->app->make(HeroBattleService::class);
    }

    protected function dominionWithHero(string $heroName, int $experience = 4500): Dominion
    {
        $dominion = $this->createDominionWithLegacyStats($this->createUser(), $this->round, Race::where('name', 'Human')->firstOrFail(), $this->realm);
        Hero::create([
            'dominion_id' => $dominion->id,
            'name' => $heroName,
            'class' => 'blacksmith',
            'experience' => $experience,
            'class_data' => [],
        ]);

        return $dominion->refresh();
    }

    public function testItNeedsARealmmatesHero(): void
    {
        $pilot = $this->dominionWithHero('Pilot');

        $this->expectException(GameException::class);
        $this->heroBattleService->createPracticeBattle($pilot, 'evil_twin_2v2');
    }

    public function testEachHeroFacesItsOwnEvilTwin(): void
    {
        $pilot = $this->dominionWithHero('Pilot', 1200);
        $lender = $this->dominionWithHero('Lender', 10000);

        $battle = $this->heroBattleService->createPracticeBattle($pilot, 'evil_twin_2v2', [$lender->hero]);

        $ours = $battle->combatants()->where('team', 1)->get();
        $theirs = $battle->combatants()->where('team', 2)->get();
        $this->assertEqualsCanonicalizing([$pilot->hero->id, $lender->hero->id], $ours->pluck('hero_id')->all());
        $this->assertTrue($ours->every(fn ($combatant) => $combatant->controller_dominion_id === $pilot->id));
        $this->assertCount(2, $theirs);
        $this->assertTrue($theirs->every(fn ($combatant) => $combatant->template_key === 'evil_twin'));

        $heroCalculator = $this->app->make(HeroCalculator::class);
        foreach ([$pilot->hero, $lender->hero] as $hero) {
            $twin = $theirs->first(fn ($combatant) => str_contains($combatant->name, $hero->name));
            $this->assertNotNull($twin, "{$hero->name} has a twin");
            foreach ($heroCalculator->getHeroCombatStats($hero) as $stat => $value) {
                $this->assertEquals($value, $twin->{$stat}, "{$hero->name}'s twin copies {$stat}");
            }
        }
    }

    public function testTheTurnWaitsForOrdersForBothHeroes(): void
    {
        $pilot = $this->dominionWithHero('Pilot');
        $lender = $this->dominionWithHero('Lender');
        $battle = $this->heroBattleService->createPracticeBattle($pilot, 'evil_twin_2v2', [$lender->hero]);
        $hero = $battle->combatants()->where('hero_id', $pilot->hero->id)->firstOrFail();
        $borrowed = $battle->combatants()->where('hero_id', $lender->hero->id)->firstOrFail();
        $twin = $battle->combatants()->where('team', 2)->firstOrFail();

        $this->assertFalse($this->heroBattleService->processTurn($battle->fresh()), 'Nothing resolves without orders');

        $this->app->make(HeroActionService::class)->queueOrders($pilot, $battle->fresh(), [
            $hero->id => ['action' => 'attack', 'target' => $twin->id],
            $borrowed->id => ['action' => 'defend', 'target' => $hero->id],
        ]);
        $this->assertTrue($this->heroBattleService->processTurn($battle->fresh()));
        $this->assertEquals(2, $battle->fresh()->current_turn);
    }

    public function testItIsListedRightUnderEvilTwin(): void
    {
        $pilot = $this->dominionWithHero('Pilot');
        $this->dominionWithHero('Lender');
        $this->actingAs($pilot->user);
        $this->selectDominion($pilot);

        $content = $this->visitRoute('dominion.heroes.battles.practice')->response->getContent();

        $this->assertStringContainsString('value="evil_twin_2v2" data-party-size="2" data-min-party-size="2"', $content);
        $this->assertLessThan(strpos($content, 'value="rabid_bunny"'), strpos($content, 'value="evil_twin_2v2"'));
    }
}
