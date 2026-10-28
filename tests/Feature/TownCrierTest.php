<?php

namespace OpenDominion\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundLeague;
use OpenDominion\Services\GameEventService;
use OpenDominion\Tests\AbstractTestCase;

class TownCrierTest extends AbstractTestCase
{
    protected Round $round;
    protected Realm $realm;
    protected Realm $otherRealm;
    protected Dominion $viewer;
    protected Dominion $human;
    protected Dominion $bot;
    protected GameEventService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-25 12:00:00'));
        $this->round = $this->createTownCrierRound();
        $this->round->created_at = now()->subDays(5);
        $this->round->save();
        $this->realm = $this->createRealm($this->round);
        $this->otherRealm = $this->createRealm($this->round);
        $this->viewer = $this->createDominion($this->createUser(), $this->round, null, $this->realm);
        $this->human = $this->createDominion($this->createUser(), $this->round, null, $this->otherRealm);
        $this->bot = $this->createDominion($this->createUser(), $this->round, null, $this->otherRealm, ['user_id' => null]);
        $this->service = $this->app->make(GameEventService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testBotAttackScopesAndClassificationOnlyMatchInvasionsFromBots(): void
    {
        $this->human->update(['abandoned_at' => now()->subDay()]);
        $botAttack = $this->createEvent($this->bot, $this->viewer);
        $humanAttackOnBot = $this->createEvent($this->human, $this->bot);
        $raid = $this->createEvent($this->bot, $this->viewer, 'raid_attacked');
        $wonder = $this->createEvent($this->bot, $this->viewer, 'wonder_attacked');
        $abandonment = $this->createEvent($this->bot, null, 'dominion_abandoned');
        $war = $this->createEvent($this->otherRealm, $this->realm, 'war_declared');
        $missingSource = $this->createEvent($this->human, $this->viewer);
        $missingSource->source_id = 2147483647;
        $missingSource->save();

        $visible = [$humanAttackOnBot, $raid, $wonder, $abandonment, $war, $missingSource];
        $this->assertTrue($botAttack->fresh()->isBotAttack());
        $this->assertTrue($botAttack->fresh(['source'])->isBotAttack());
        foreach ($visible as $event) {
            $this->assertFalse($event->fresh()->isBotAttack());
        }

        $events = GameEvent::query()->where('round_id', $this->round->id);
        $this->assertSame([$botAttack->id], (clone $events)->botAttacks()->pluck('id')->all());
        $this->assertEqualsCanonicalizing(
            array_map(fn (GameEvent $event): string => $event->id, $visible),
            (clone $events)->withoutBotAttacks()->pluck('id')->all()
        );
    }

    public function testBotAttacksAreExcludedBeforePagination(): void
    {
        $humanAttack = $this->createEvent($this->human, $this->bot, 'invasion', now()->subHours(2));
        $raid = $this->createEvent($this->bot, $this->viewer, 'raid_attacked', now()->subHours(2));
        $war = $this->createEvent($this->otherRealm, $this->realm, 'war_declared', now()->subHours(2));
        for ($index = 0; $index < 105; $index++) {
            $this->createEvent($this->bot, $this->viewer);
        }

        $result = $this->service->getTownCrier($this->viewer, null, null, 'all_without_bots');

        $this->assertSame(3, $result['gameEvents']->total());
        $this->assertCount(3, $result['gameEvents']);
        $this->assertSame(1, $result['gameEvents']->lastPage());
        $this->assertEqualsCanonicalizing(
            [$humanAttack->id, $raid->id, $war->id],
            $result['gameEvents']->pluck('id')->all()
        );

        $expanded = $this->service->getTownCrier($this->viewer);

        $this->assertSame(108, $expanded['gameEvents']->total());
        $this->assertCount(100, $expanded['gameEvents']);
        $this->assertSame(2, $expanded['gameEvents']->lastPage());
    }

    public function testRealmAndTypeFiltersExcludeFutureAndOtherRoundEvents(): void
    {
        $humanAttack = $this->createEvent($this->human, $this->viewer);
        $this->createEvent($this->bot, $this->viewer);
        $this->createEvent($this->bot, $this->human);
        $this->createEvent($this->viewer, $this->bot, 'wonder_attacked');
        $this->createEvent($this->bot, $this->viewer, 'invasion', now()->addHour());
        $this->createEvent($this->bot, $this->viewer, 'invasion', null, $this->createTownCrierRound());

        $result = $this->service->getTownCrier($this->viewer, $this->realm, null, 'invasions_without_bots');

        $this->assertSame([$humanAttack->id], $result['gameEvents']->pluck('id')->all());
        $this->assertSame([$this->viewer->id], $result['dominionIds']);
    }

    public function testDominionAndTypeFiltersRestrictResultsWithBothVisibilityChoices(): void
    {
        $humanAttack = $this->createEvent($this->human, $this->viewer);
        $botAttack = $this->createEvent($this->bot, $this->viewer);
        $this->createEvent($this->bot, $this->human);
        $this->createEvent($this->human, $this->bot);
        $this->createEvent($this->bot, $this->viewer, 'raid_attacked');

        $result = $this->service->getTownCrier($this->viewer, $this->realm, $this->viewer, 'invasions_without_bots');

        $this->assertSame([$humanAttack->id], $result['gameEvents']->pluck('id')->all());
        $this->assertSame([$this->viewer->id], $result['dominionIds']);

        $expanded = $this->service->getTownCrier($this->viewer, $this->realm, $this->viewer, 'invasions');

        $this->assertEqualsCanonicalizing([$humanAttack->id, $botAttack->id], $expanded['gameEvents']->pluck('id')->all());
    }

    public function testNonInvasionFiltersReturnOnlyMatchingEvents(): void
    {
        $this->createEvent($this->bot, $this->viewer);
        $wonder = $this->createEvent($this->bot, $this->viewer, 'wonder_attacked');
        $raid = $this->createEvent($this->bot, $this->viewer, 'raid_attacked');
        $war = $this->createEvent($this->otherRealm, $this->realm, 'war_declared');

        foreach (['wonders' => $wonder, 'raids' => $raid, 'wars' => $war] as $type => $event) {
            $result = $this->service->getTownCrier($this->viewer, $this->realm, null, $type);

            $this->assertSame([$event->id], $result['gameEvents']->pluck('id')->all());
        }
    }

    public function testClairvoyanceAndExistingDominionHistoryIncludeBotAttacks(): void
    {
        $humanAttack = $this->createEvent($this->human, $this->viewer);
        $botAttack = $this->createEvent($this->bot, $this->viewer);
        $this->createEvent($this->bot, $this->viewer, 'invasion', now()->addMinute());

        foreach ([
            $this->service->getClairvoyance($this->realm, now()),
            $this->service->getGameEventsForDominion($this->viewer, now()),
        ] as $result) {
            $this->assertEqualsCanonicalizing([$humanAttack->id, $botAttack->id], $result['gameEvents']->pluck('id')->all());
        }
    }

    public function testUnreadCountsUseSharedCursorAndCurrentRound(): void
    {
        $this->viewer->update(['town_crier_last_seen' => now()->subHour()]);
        $this->createEvent($this->human, $this->viewer, 'invasion', now()->subMinutes(30));
        $this->createEvent($this->human, $this->viewer, 'invasion', now()->subMinutes(90));
        $this->createEvent($this->bot, $this->viewer, 'invasion', now()->subMinutes(30));
        $this->createEvent($this->bot, $this->viewer, 'invasion', now()->subMinutes(90));
        $this->createEvent($this->bot, $this->viewer, 'raid_attacked', now()->subMinutes(20));
        $missingSource = $this->createEvent($this->human, $this->viewer);
        $missingSource->source_id = 2147483647;
        $missingSource->save();
        $wrongSourceType = $this->createEvent($this->bot, $this->viewer);
        $wrongSourceType->source_type = Realm::class;
        $wrongSourceType->save();
        $otherRound = $this->createTownCrierRound();
        $this->createEvent($this->human, $this->viewer, 'invasion', null, $otherRound);
        $this->createEvent($this->bot, $this->viewer, 'invasion', null, $otherRound);

        $this->viewer->load('round');
        $connection = $this->viewer->getConnection();
        $connection->flushQueryLog();
        $connection->enableQueryLog();
        try {
            $counts = $this->service->getUnreadCounts($this->viewer);
            $queries = $connection->getQueryLog();
        } finally {
            $connection->disableQueryLog();
        }

        $this->assertSame(['events' => 4, 'botAttacks' => 1], $counts);
        $this->assertCount(1, $queries);
    }

    public function testFirstVisitFallsBackToRoundCreationForBothUnreadCategories(): void
    {
        $this->viewer->update(['town_crier_last_seen' => null, 'settings' => null]);
        $this->assertSame(['events' => 0, 'botAttacks' => 0], $this->service->getUnreadCounts($this->viewer));
        $this->createEvent($this->human, $this->viewer);
        $this->createEvent($this->bot, $this->viewer);
        $this->createEvent($this->human, $this->viewer, 'invasion', $this->round->created_at->copy()->subMinute());
        $this->createEvent($this->bot, $this->viewer, 'invasion', $this->round->created_at->copy()->subMinute());

        $this->assertSame(['events' => 1, 'botAttacks' => 1], $this->service->getUnreadCounts($this->viewer));
    }

    public function testInvasionEventsHighlightBotDominionNamesInMagenta(): void
    {
        $invasionData = ['result' => ['success' => true], 'attacker' => ['landConquered' => ['plain' => 10]]];
        $botAttack = $this->createEvent($this->bot, $this->human, 'invasion', null, null, $invasionData);
        $humanAttackOnBot = $this->createEvent($this->human, $this->bot, 'invasion', null, null, ['result' => ['success' => false]]);
        $viewerAttackOnBot = $this->createEvent($this->viewer, $this->bot, 'invasion', null, null, $invasionData);

        $botAttackHtml = $this->renderGameEvent($botAttack);
        $this->assertNameHasClass($botAttackHtml, $this->bot, 'text-magenta');
        $this->assertNameHasClass($botAttackHtml, $this->human, 'text-light-blue');

        $humanAttackOnBotHtml = $this->renderGameEvent($humanAttackOnBot);
        $this->assertNameHasClass($humanAttackOnBotHtml, $this->bot, 'text-magenta');
        $this->assertNameHasClass($humanAttackOnBotHtml, $this->human, 'text-light-blue');

        $viewerAttackOnBotHtml = $this->renderGameEvent($viewerAttackOnBot);
        $this->assertNameHasClass($viewerAttackOnBotHtml, $this->bot, 'text-magenta');
        $this->assertNameHasClass($viewerAttackOnBotHtml, $this->viewer, 'text-green');
    }

    protected function renderGameEvent(GameEvent $gameEvent): string
    {
        return view('partials.dominion.game-event', [
            'gameEvent' => $gameEvent,
            'selectedDominion' => $this->viewer,
            'dominionIds' => [$this->viewer->id],
            'rangeCalculator' => $this->app->make(RangeCalculator::class),
        ])->render();
    }

    protected function assertNameHasClass(string $html, Dominion $dominion, string $class): void
    {
        $this->assertMatchesRegularExpression(
            '/<span class="' . preg_quote($class, '/') . '"[^>]*>' . preg_quote(e($dominion->name), '/') . '<\/span>/',
            $html
        );
    }

    protected function createTownCrierRound(): Round
    {
        $league = RoundLeague::firstOrCreate(['key' => 'standard'], ['description' => 'Standard']);

        return Round::create([
            'round_league_id' => $league->id,
            'number' => 1,
            'name' => 'Town Crier Testing Round',
            'start_date' => now()->subDays(4),
            'end_date' => now()->addDays(40),
            'pack_size' => 6,
        ]);
    }

    protected function createEvent(Model $source, ?Model $target, string $type = 'invasion', ?Carbon $createdAt = null, ?Round $round = null, array $data = []): GameEvent
    {
        $event = new GameEvent([
            'round_id' => ($round ?? $this->round)->id,
            'type' => $type,
            'data' => $data,
        ]);
        $event->source()->associate($source);
        $event->target()->associate($target);
        $event->created_at = $createdAt ?? now()->subMinute();
        $event->save();

        return $event->refresh();
    }
}
