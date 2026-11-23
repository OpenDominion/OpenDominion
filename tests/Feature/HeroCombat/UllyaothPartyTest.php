<?php

namespace OpenDominion\Tests\Feature\HeroCombat;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Exceptions\GameException;
use OpenDominion\HeroCombat\Engine\Events\EventType;
use OpenDominion\HeroCombat\Engine\Events\MessageKind;
use OpenDominion\HeroCombat\Persistence\BattleRepository;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hero;
use OpenDominion\Models\HeroBattle;
use OpenDominion\Models\HeroBattleTurn;
use OpenDominion\Models\HeroCombatant;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\Actions\HeroActionService;
use OpenDominion\Services\Dominion\HeroBattleService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

/**
 * The Ullyaoth encounter as a party fight: it needs a realmmate's hero, both heroes carry the
 * Titan Spear, and only one of them may wield it each turn.
 */
class UllyaothPartyTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    protected Round $round;

    protected Realm $realm;

    protected HeroBattleService $heroBattleService;

    protected HeroActionService $heroActionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->round = $this->createRound('-3 days midnight');
        $this->realm = $this->createRealm($this->round);
        $this->heroBattleService = $this->app->make(HeroBattleService::class);
        $this->heroActionService = $this->app->make(HeroActionService::class);
    }

    protected function dominionWithHero(string $heroName): Dominion
    {
        $dominion = $this->createDominionWithLegacyStats($this->createUser(), $this->round, Race::where('name', 'Human')->firstOrFail(), $this->realm);
        Hero::create([
            'dominion_id' => $dominion->id,
            'name' => $heroName,
            'class' => 'blacksmith',
            'experience' => 2000,
            'class_data' => [],
        ]);

        return $dominion->refresh();
    }

    /**
     * @return array{0: Dominion, 1: HeroBattle, 2: HeroCombatant, 3: HeroCombatant, 4: HeroCombatant}
     */
    protected function party(): array
    {
        $pilot = $this->dominionWithHero('Pilot');
        $lender = $this->dominionWithHero('Lender');
        $battle = $this->heroBattleService->createPracticeBattle($pilot, 'ullyaoth', [$lender->hero]);

        $hero = $battle->combatants()->where('hero_id', $pilot->hero->id)->firstOrFail();
        $borrowed = $battle->combatants()->where('hero_id', $lender->hero->id)->firstOrFail();
        $ullyaoth = $battle->combatants()->where('template_key', 'ullyaoth')->firstOrFail();

        return [$pilot, $battle, $hero, $borrowed, $ullyaoth];
    }

    /**
     * Makes the battle's current turn a Devour turn, aimed at `$target`.
     */
    protected function makeDevourTurn(HeroBattle $heroBattle, HeroCombatant $target): void
    {
        $repository = $this->app->make(BattleRepository::class);
        $battle = $repository->load($heroBattle);
        $ullyaoth = $battle->withTemplate('ullyaoth')[0];
        $rain = $battle->effects->find($ullyaoth, 'blood_rain');
        $rain->data['devour_turn'] = $battle->turn();
        $rain->data['target'] = $target->id;
        $repository->persist($battle, $heroBattle);
    }

    public function testUllyaothNeedsARealmmatesHero(): void
    {
        $pilot = $this->dominionWithHero('Pilot');

        $this->expectException(GameException::class);
        $this->heroBattleService->createPracticeBattle($pilot, 'ullyaoth');
    }

    public function testBothHeroesCarryTheSpearAgainstUllyaothAndItsChampion(): void
    {
        [, $battle, $hero, $borrowed] = $this->party();

        $this->assertContains('titan_spear', $hero->abilities);
        $this->assertContains('titan_spear', $borrowed->abilities);
        $this->assertEqualsCanonicalizing(
            ['ullyaoth', 'bloodbound_orc'],
            $battle->combatants()->where('team', 2)->pluck('template_key')->all(),
        );
    }

    public function testTheSpearCannotBeLockedInOffADevourTurn(): void
    {
        [$pilot, $battle, $hero, $borrowed, $ullyaoth] = $this->party();

        $this->expectException(GameException::class);
        $this->heroActionService->queueOrders($pilot, $battle, [
            $hero->id => ['action' => 'titan_spear', 'target' => $ullyaoth->id],
            $borrowed->id => ['action' => 'defend', 'target' => $hero->id],
        ]);
    }

    public function testOneHeroCanLockInTheSpearOnADevourTurn(): void
    {
        [$pilot, $battle, $hero, $borrowed, $ullyaoth] = $this->party();
        $this->makeDevourTurn($battle, $hero);

        $this->heroActionService->queueOrders($pilot, $battle->fresh(), [
            $hero->id => ['action' => 'titan_spear', 'target' => $ullyaoth->id],
            $borrowed->id => ['action' => 'defend', 'target' => $hero->id],
        ]);

        $this->assertEquals('titan_spear', $hero->fresh()->actions[0]['ability']);
    }

    public function testOnlyOneHeroCanLockInTheSpear(): void
    {
        [$pilot, $battle, $hero, $borrowed, $ullyaoth] = $this->party();
        $this->makeDevourTurn($battle, $hero);

        try {
            $this->heroActionService->queueOrders($pilot, $battle->fresh(), [
                $hero->id => ['action' => 'titan_spear', 'target' => $ullyaoth->id],
                $borrowed->id => ['action' => 'titan_spear', 'target' => $ullyaoth->id],
            ]);
            $this->fail('Two heroes locked in the Spear');
        } catch (GameException) {
            $this->assertEmpty($hero->fresh()->actions, 'Lock-in is all or nothing');
        }
    }

    public function testTheOrdersPanelMarksTheSpearAsOneHeroOnly(): void
    {
        [$pilot] = $this->party();
        $this->actingAs($pilot->user);
        $this->selectDominion($pilot);

        $this->visitRoute('dominion.heroes.battles');

        $this->assertStringContainsString('data-action="titan_spear"', $this->response->getContent());
        $this->assertStringContainsString('data-one-per-team', $this->response->getContent());
    }

    public function testTheLastTurnsTellsShowAboveTheOrders(): void
    {
        [$pilot, $battle] = $this->party();
        $battle->update(['current_turn' => 2]);
        HeroBattleTurn::record($battle, 1, [
            ['type' => EventType::Message->value, 'actor' => null, 'text' => 'Tell for next turn', 'kind' => MessageKind::Telegraph->value],
            ['type' => EventType::Message->value, 'actor' => null, 'text' => 'Ordinary narration', 'kind' => MessageKind::Narration->value],
        ]);
        HeroBattleTurn::record($battle, 0, [
            ['type' => EventType::Message->value, 'actor' => null, 'text' => 'Stale tell', 'kind' => MessageKind::Telegraph->value],
        ]);
        $this->actingAs($pilot->user);
        $this->selectDominion($pilot);

        $this->visitRoute('dominion.heroes.battles');

        $this->assertMatchesRegularExpression('/data-battle-tells>(.*?)<\/div>\s*<\/div>/s', $this->response->getContent());
        preg_match('/data-battle-tells>(.*?)<\/div>\s*<\/div>/s', $this->response->getContent(), $tells);
        $this->assertStringContainsString('Tell for next turn', $tells[1]);
        $this->assertStringNotContainsString('Ordinary narration', $tells[1]);
        $this->assertStringNotContainsString('Stale tell', $tells[1]);
    }

    public function testNoTellsMeansNoBanner(): void
    {
        [$pilot] = $this->party();
        $this->actingAs($pilot->user);
        $this->selectDominion($pilot);

        $this->visitRoute('dominion.heroes.battles');

        $this->assertStringNotContainsString('data-battle-tells', $this->response->getContent());
    }
}
