<?php

namespace OpenDominion\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
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

        $result = $this->service->getTownCrier($this->viewer);

        $this->assertSame(3, $result['gameEvents']->total());
        $this->assertCount(3, $result['gameEvents']);
        $this->assertSame(1, $result['gameEvents']->lastPage());
        $this->assertSame(105, $result['hiddenBotAttackCount']);
        $this->assertEqualsCanonicalizing(
            [$humanAttack->id, $raid->id, $war->id],
            $result['gameEvents']->pluck('id')->all()
        );

        $expanded = $this->service->getTownCrier($this->viewer, null, null, 'all', true);

        $this->assertSame(108, $expanded['gameEvents']->total());
        $this->assertCount(100, $expanded['gameEvents']);
        $this->assertSame(2, $expanded['gameEvents']->lastPage());
        $this->assertSame(0, $expanded['hiddenBotAttackCount']);
    }

    public function testRealmAndTypeFiltersAlsoRestrictHiddenAttackCount(): void
    {
        $humanAttack = $this->createEvent($this->human, $this->viewer);
        $this->createEvent($this->bot, $this->viewer);
        $this->createEvent($this->bot, $this->human);
        $this->createEvent($this->viewer, $this->bot, 'wonder_attacked');
        $this->createEvent($this->bot, $this->viewer, 'invasion', now()->addHour());
        $this->createEvent($this->bot, $this->viewer, 'invasion', null, $this->createTownCrierRound());

        $result = $this->service->getTownCrier($this->viewer, $this->realm, null, 'invasions');

        $this->assertSame([$humanAttack->id], $result['gameEvents']->pluck('id')->all());
        $this->assertSame(1, $result['hiddenBotAttackCount']);
        $this->assertSame([$this->viewer->id], $result['dominionIds']);
    }

    public function testDominionAndTypeFiltersRestrictResultsWithBothVisibilityChoices(): void
    {
        $humanAttack = $this->createEvent($this->human, $this->viewer);
        $botAttack = $this->createEvent($this->bot, $this->viewer);
        $this->createEvent($this->bot, $this->human);
        $this->createEvent($this->human, $this->bot);
        $this->createEvent($this->bot, $this->viewer, 'raid_attacked');

        $result = $this->service->getTownCrier($this->viewer, $this->realm, $this->viewer, 'invasions');

        $this->assertSame([$humanAttack->id], $result['gameEvents']->pluck('id')->all());
        $this->assertSame(1, $result['hiddenBotAttackCount']);
        $this->assertSame([$this->viewer->id], $result['dominionIds']);

        $expanded = $this->service->getTownCrier($this->viewer, $this->realm, $this->viewer, 'invasions', true);

        $this->assertEqualsCanonicalizing([$humanAttack->id, $botAttack->id], $expanded['gameEvents']->pluck('id')->all());
        $this->assertSame(0, $expanded['hiddenBotAttackCount']);
    }

    public function testNonInvasionFiltersDoNotReportHiddenBotAttacks(): void
    {
        $this->createEvent($this->bot, $this->viewer);
        $wonder = $this->createEvent($this->bot, $this->viewer, 'wonder_attacked');
        $raid = $this->createEvent($this->bot, $this->viewer, 'raid_attacked');
        $war = $this->createEvent($this->otherRealm, $this->realm, 'war_declared');

        foreach (['wonders' => $wonder, 'raids' => $raid, 'wars' => $war] as $type => $event) {
            $result = $this->service->getTownCrier($this->viewer, $this->realm, null, $type);

            $this->assertSame([$event->id], $result['gameEvents']->pluck('id')->all());
            $this->assertSame(0, $result['hiddenBotAttackCount']);
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

    public function testUnreadCountsUseSeparateCursorsAndCurrentRound(): void
    {
        $this->viewer->update([
            'town_crier_last_seen' => now()->subHour(),
            'settings' => ['town_crier_bot_attacks_last_seen' => now()->subHours(2)->toDateTimeString()],
        ]);
        $this->createEvent($this->human, $this->viewer, 'invasion', now()->subMinutes(30));
        $this->createEvent($this->human, $this->viewer, 'invasion', now()->subMinutes(90));
        $this->createEvent($this->bot, $this->viewer, 'invasion', now()->subMinutes(90));
        $this->createEvent($this->bot, $this->viewer, 'invasion', now()->subHours(3));
        $this->createEvent($this->bot, $this->viewer, 'raid_attacked', now()->subMinutes(20));
        $otherRound = $this->createTownCrierRound();
        $this->createEvent($this->human, $this->viewer, 'invasion', null, $otherRound);
        $this->createEvent($this->bot, $this->viewer, 'invasion', null, $otherRound);

        $this->assertSame(['events' => 2, 'botAttacks' => 1], $this->service->getUnreadCounts($this->viewer));
    }

    public function testReadingOrdinaryNewsPreservesUnreadBotAttacksUntilExpanded(): void
    {
        $previousSeen = now()->subHour();
        $this->viewer->update(['town_crier_last_seen' => $previousSeen, 'settings' => ['other_preference' => 'retained']]);
        $this->createEvent($this->human, $this->viewer);
        $this->createEvent($this->bot, $this->viewer);

        $this->service->markTownCrierRead($this->viewer, false);
        $this->viewer->refresh();

        $this->assertTrue($this->viewer->town_crier_last_seen->equalTo(now()));
        $this->assertTrue(Carbon::parse($this->viewer->settings['town_crier_bot_attacks_last_seen'])->equalTo($previousSeen));
        $this->assertSame('retained', $this->viewer->settings['other_preference']);
        $this->assertSame(['events' => 0, 'botAttacks' => 1], $this->service->getUnreadCounts($this->viewer));

        Carbon::setTestNow(now()->addMinutes(10));
        $this->createEvent($this->human, $this->viewer);
        $this->createEvent($this->bot, $this->viewer);
        $this->service->markTownCrierRead($this->viewer, false);
        $this->viewer->refresh();

        $this->assertSame(['events' => 0, 'botAttacks' => 2], $this->service->getUnreadCounts($this->viewer));
        $this->assertTrue(Carbon::parse($this->viewer->settings['town_crier_bot_attacks_last_seen'])->equalTo($previousSeen));

        $this->service->markTownCrierRead($this->viewer, true);
        $this->viewer->refresh();

        $this->assertTrue(Carbon::parse($this->viewer->settings['town_crier_bot_attacks_last_seen'])->equalTo(now()));
        $this->assertSame(['events' => 0, 'botAttacks' => 0], $this->service->getUnreadCounts($this->viewer));
    }

    public function testFirstVisitFallsBackToRoundCreationForBothUnreadCursors(): void
    {
        $this->viewer->update(['town_crier_last_seen' => null, 'settings' => null]);
        $this->createEvent($this->human, $this->viewer);
        $this->createEvent($this->bot, $this->viewer);
        $this->createEvent($this->bot, $this->viewer, 'invasion', $this->round->created_at->copy()->subMinute());

        $this->assertSame(['events' => 1, 'botAttacks' => 1], $this->service->getUnreadCounts($this->viewer));

        $this->service->markTownCrierRead($this->viewer, false);
        $this->viewer->refresh();

        $this->assertTrue(Carbon::parse($this->viewer->settings['town_crier_bot_attacks_last_seen'])->equalTo($this->round->created_at));
        $this->assertSame(['events' => 0, 'botAttacks' => 1], $this->service->getUnreadCounts($this->viewer));
    }

    public function testBotAttackPreferencePersistsWithoutChangingResourcesOrOtherSettings(): void
    {
        $this->viewer->update(['settings' => ['other_preference' => ['enabled' => true]]]);
        $lastTick = $this->viewer->getRawOriginal('last_tick_at');
        $platinum = $this->viewer->resource_platinum;
        $lastSeen = $this->viewer->town_crier_last_seen;

        $this->assertFalse($this->service->shouldShowBotAttacks($this->viewer));
        $this->service->setShowBotAttacks($this->viewer, true);
        $this->viewer->refresh();

        $this->assertTrue($this->viewer->settings['show_bot_attacks']);
        $this->assertTrue($this->service->shouldShowBotAttacks($this->viewer));
        $this->assertSame(['enabled' => true], $this->viewer->settings['other_preference']);
        $this->assertSame($lastTick, $this->viewer->getRawOriginal('last_tick_at'));
        $this->assertSame($platinum, $this->viewer->resource_platinum);
        $this->assertEquals($lastSeen, $this->viewer->town_crier_last_seen);

        $this->service->setShowBotAttacks($this->viewer, false);
        $this->viewer->refresh();

        $this->assertFalse($this->viewer->settings['show_bot_attacks']);
        $this->assertFalse($this->service->shouldShowBotAttacks($this->viewer));
        $this->assertSame(['enabled' => true], $this->viewer->settings['other_preference']);
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

    protected function createEvent(Model $source, ?Model $target, string $type = 'invasion', ?Carbon $createdAt = null, ?Round $round = null): GameEvent
    {
        $event = new GameEvent([
            'round_id' => ($round ?? $this->round)->id,
            'type' => $type,
            'data' => [],
        ]);
        $event->source()->associate($source);
        $event->target()->associate($target);
        $event->created_at = $createdAt ?? now()->subMinute();
        $event->save();

        return $event->refresh();
    }
}
