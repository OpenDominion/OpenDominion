<?php

namespace OpenDominion\Tests\Feature\Http\Api\V1;

use Carbon\Carbon;
use Illuminate\Routing\Middleware\ThrottleRequests;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Realm;
use OpenDominion\Models\RealmWar;
use OpenDominion\Models\Round;
use OpenDominion\Tests\AbstractTestCase;

class RoundApiTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function testRoundsIndexIncludesLeagueAndTiming(): void
    {
        $round = $this->createRound();

        $response = $this->getJson('/api/v1/rounds')->assertOk();
        $rounds = collect($response->json())->keyBy('id');

        $this->assertTrue($rounds->has($round->id));
        $entry = $rounds->get($round->id);
        $this->assertSame($round->number, $entry['number']);
        $this->assertSame($round->name, $entry['name']);
        $this->assertNotNull($entry['league']);
        $this->assertArrayHasKey('key', $entry['league']);
        $this->assertArrayHasKey('has_started', $entry);
        $this->assertArrayHasKey('has_ended', $entry);
        $this->assertMatchesRegularExpression('/^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}Z$/', $entry['start_date']);
        $this->assertMatchesRegularExpression('/^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}Z$/', $entry['end_date']);
    }

    public function testRoundsIndexIsOrderedByStartDateDescending(): void
    {
        $older = $this->createRound('-30 days', '+10 days');
        $newer = $this->createRound('-5 days', '+40 days');

        $ids = collect($this->getJson('/api/v1/rounds')->json())->pluck('id')->all();

        $newerPos = array_search($newer->id, $ids, true);
        $olderPos = array_search($older->id, $ids, true);

        $this->assertNotFalse($newerPos);
        $this->assertNotFalse($olderPos);
        $this->assertLessThan($olderPos, $newerPos, 'Newer rounds should appear before older ones.');
    }

    public function testDominionsListReturnsLandAndNetworth(): void
    {
        $round = $this->createRound();
        $user = $this->createUser();
        $dominion = $this->createDominion($user, $round);

        $response = $this->getJson('/api/v1/rounds/' . $round->id . '/dominions')->assertOk();

        $entries = collect($response->json())->keyBy('id');
        $this->assertTrue($entries->has($dominion->id));

        $entry = $entries->get($dominion->id);
        $this->assertArrayHasKey('land', $entry);
        $this->assertArrayHasKey('networth', $entry);
        $this->assertSame($dominion->realm->number, $entry['realm_number']);
        $this->assertIsInt($entry['land']);
        $this->assertSame(
            ['id', 'name', 'race', 'realm_number', 'realm_name', 'land', 'networth', 'in_protection'],
            array_keys($entry)
        );
    }

    public function testDominionsListExcludesLockedAndAbandonedDominions(): void
    {
        $round = $this->createRound();
        $active = $this->createDominion($this->createUser(), $round);
        $locked = $this->createDominion($this->createUser(), $round);
        $locked->update(['locked_at' => now()]);
        $abandoned = $this->createDominion($this->createUser(), $round);
        $abandoned->update(['abandoned_at' => now()->subDay()]);

        $ids = collect($this->getJson('/api/v1/rounds/' . $round->id . '/dominions')->json())
            ->pluck('id')->all();

        $this->assertContains($active->id, $ids);
        $this->assertNotContains($locked->id, $ids);
        $this->assertNotContains($abandoned->id, $ids);
    }

    public function testEventsRespectsLimit(): void
    {
        $round = $this->createRound();

        for ($i = 0; $i < 5; $i++) {
            GameEvent::create([
                'round_id' => $round->id,
                'source_type' => Dominion::class,
                'source_id' => $i + 1,
                'type' => 'invasion',
                'data' => ['seq' => $i],
            ]);
        }

        $events = $this->getJson('/api/v1/rounds/' . $round->id . '/events?limit=3')
            ->assertOk()
            ->json();

        $this->assertCount(3, $events);
    }

    public function testInvasionEventExposesOnlyTownCrierFields(): void
    {
        $round = $this->createRound();
        $attacker = $this->createDominion($this->createUser(), $round);
        $defender = $this->createDominion($this->createUser(), $round);

        $event = GameEvent::create([
            'round_id' => $round->id,
            'source_type' => Dominion::class,
            'source_id' => $attacker->id,
            'target_type' => Dominion::class,
            'target_id' => $defender->id,
            'type' => 'invasion',
            'data' => [
                'result' => ['success' => true, 'range' => 82.5],
                'attacker' => [
                    'landConquered' => ['plain' => 10, 'forest' => 5],
                    'landGenerated' => ['plain' => 3, 'forest' => 1],
                    'landGained' => 19,
                    'unitsSent' => ['secretUnits' => 500],
                ],
                'defender' => ['unitsLost' => ['secretLosses' => 100]],
            ],
        ]);

        $response = $this->getJson('/api/v1/rounds/' . $round->id . '/events')->assertOk();
        $entry = collect($response->json())->firstWhere('id', (string) $event->id);

        $this->assertSame([
            'id' => (string) $event->id,
            'type' => 'invasion',
            'source_type' => 'dominion',
            'source_id' => $attacker->id,
            'target_type' => 'dominion',
            'target_id' => $defender->id,
            'data' => ['success' => true, 'land_lost' => 15, 'land_gained' => 19],
            'created_at' => $event->created_at->toIso8601ZuluString(),
        ], $entry);
        $this->assertMatchesRegularExpression('/^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}Z$/', $entry['created_at']);
        $this->assertStringNotContainsString('secret', $response->getContent());
        $this->assertStringNotContainsString('OpenDominion', $response->getContent());
    }

    public function testWarEventExposesRealmNumbersAndNames(): void
    {
        $round = $this->createRound();
        $sourceRealm = Realm::create(['round_id' => $round->id, 'alignment' => 'good', 'number' => 3, 'name' => 'Aggressors']);
        $targetRealm = Realm::create(['round_id' => $round->id, 'alignment' => 'evil', 'number' => 7, 'name' => 'Defenders']);
        $war = RealmWar::create([
            'source_realm_id' => $sourceRealm->id,
            'source_realm_name_start' => 'Aggressors',
            'target_realm_id' => $targetRealm->id,
            'target_realm_name_start' => 'Defenders',
        ]);

        $event = GameEvent::create([
            'round_id' => $round->id,
            'source_type' => Realm::class,
            'source_id' => $sourceRealm->id,
            'target_type' => RealmWar::class,
            'target_id' => $war->id,
            'type' => 'war_declared',
            'data' => ['monarchDominionID' => 12345],
        ]);

        $response = $this->getJson('/api/v1/rounds/' . $round->id . '/events')->assertOk();
        $entry = collect($response->json())->firstWhere('id', (string) $event->id);

        $this->assertSame('realm', $entry['source_type']);
        $this->assertSame('realm_war', $entry['target_type']);
        $this->assertSame([
            'source_realm' => ['number' => 3, 'name' => 'Aggressors'],
            'target_realm' => ['number' => 7, 'name' => 'Defenders'],
        ], $entry['data']);
        $this->assertStringNotContainsString('monarchDominionID', $response->getContent());
    }

    public function testAbandonedEventHasNullTargetAndEmptyDataObject(): void
    {
        $round = $this->createRound();
        $dominion = $this->createDominion($this->createUser(), $round);

        $event = GameEvent::create([
            'round_id' => $round->id,
            'source_type' => Dominion::class,
            'source_id' => $dominion->id,
            'type' => 'abandoned',
        ]);

        $response = $this->getJson('/api/v1/rounds/' . $round->id . '/events')->assertOk();
        $entry = collect($response->json())->firstWhere('id', (string) $event->id);

        $this->assertNotNull($entry);
        $this->assertNull($entry['target_type']);
        $this->assertNull($entry['target_id']);
        $this->assertStringContainsString('"data":{}', $response->getContent());
    }

    public function testEventsRespectsSinceFilter(): void
    {
        $round = $this->createRound();

        $old = GameEvent::create([
            'round_id' => $round->id,
            'source_type' => Dominion::class,
            'source_id' => 1,
            'type' => 'invasion',
            'data' => [],
        ]);
        $old->created_at = now()->subDays(2);
        $old->save();

        $recent = GameEvent::create([
            'round_id' => $round->id,
            'source_type' => Dominion::class,
            'source_id' => 2,
            'type' => 'invasion',
            'data' => [],
        ]);

        $since = now()->subDay()->toIso8601ZuluString();
        $events = $this->getJson('/api/v1/rounds/' . $round->id . '/events?since=' . urlencode($since))
            ->assertOk()
            ->json();

        $ids = collect($events)->pluck('id')->all();
        $this->assertContains((string) $recent->id, $ids);
        $this->assertNotContains((string) $old->id, $ids);
    }

    public function testEventsExcludeNonPublicTypes(): void
    {
        $round = $this->createRound();
        $dominion = $this->createDominion($this->createUser(), $round);

        $this->createEventOfType($round, $dominion, 'abandoned');
        $sentient = $this->createEventOfType($round, $dominion, 'wonder_invasion');
        $unknown = $this->createEventOfType($round, $dominion, 'some_future_type');

        $events = collect($this->getJson('/api/v1/rounds/' . $round->id . '/events')->assertOk()->json());

        $this->assertSame(['abandoned'], $events->pluck('type')->all());
        $this->assertNotContains((string) $sentient->id, $events->pluck('id')->all());
        $this->assertNotContains((string) $unknown->id, $events->pluck('id')->all());
    }

    public function testEventsCanBeFilteredByType(): void
    {
        $round = $this->createRound();
        $dominion = $this->createDominion($this->createUser(), $round);

        $this->createEventOfType($round, $dominion, 'abandoned');
        $this->createEventOfType($round, $dominion, 'invasion');
        $this->createEventOfType($round, $dominion, 'wonder_spawned');

        $single = collect($this->getJson('/api/v1/rounds/' . $round->id . '/events?type=abandoned')->assertOk()->json());
        $this->assertSame(['abandoned'], $single->pluck('type')->all());

        $multiple = collect(
            $this->getJson('/api/v1/rounds/' . $round->id . '/events?type=abandoned,%20invasion')->assertOk()->json()
        );
        $this->assertEqualsCanonicalizing(['abandoned', 'invasion'], $multiple->pluck('type')->all());
    }

    public function testEventsRejectsUnknownOrNonPublicType(): void
    {
        $round = $this->createRound();

        foreach (['not_a_type', 'wonder_invasion', 'invasion,not_a_type'] as $type) {
            $this->getJson('/api/v1/rounds/' . $round->id . '/events?type=' . $type)
                ->assertStatus(422)
                ->assertJson(['error' => 'invalid_parameter']);
        }

        $this->getJson('/api/v1/rounds/' . $round->id . '/events?type[]=invasion')
            ->assertStatus(422)
            ->assertJson(['error' => 'invalid_parameter']);
    }

    public function testEventsRejectsInvalidSince(): void
    {
        $round = $this->createRound();

        $this->getJson('/api/v1/rounds/' . $round->id . '/events?since=not-a-date')
            ->assertStatus(422)
            ->assertJson(['error' => 'invalid_parameter']);
    }

    public function testRoundEndpointsAreLockedBeforeRoundStarts(): void
    {
        $round = $this->createRound('+2 days', '+49 days');
        $dominion = $this->createDominion($this->createUser(), $round);
        $this->createEventOfType($round, $dominion, 'abandoned');

        foreach (['dominions', 'events'] as $endpoint) {
            $response = $this->getJson('/api/v1/rounds/' . $round->id . '/' . $endpoint)
                ->assertStatus(403)
                ->assertExactJson([
                    'error' => 'round_not_started',
                    'message' => 'This endpoint is not available until the round has started.',
                ]);

            $this->assertStringNotContainsString($dominion->name, $response->getContent());
        }
    }

    public function testRoundsIndexStillListsRoundsThatHaveNotStarted(): void
    {
        $round = $this->createRound('+2 days', '+49 days');

        $entry = collect($this->getJson('/api/v1/rounds')->assertOk()->json())->firstWhere('id', $round->id);

        $this->assertNotNull($entry);
        $this->assertFalse($entry['has_started']);
    }

    public function testRoundEndpointsUnlockOnceRoundStarts(): void
    {
        $round = $this->createRound('+2 days', '+49 days');
        $this->getJson('/api/v1/rounds/' . $round->id . '/dominions')->assertStatus(403);

        $this->travelTo(now()->addDays(3));

        $this->getJson('/api/v1/rounds/' . $round->id . '/dominions')->assertOk();
        $this->getJson('/api/v1/rounds/' . $round->id . '/events')->assertOk();
    }

    public function testNonexistentRoundReturns404(): void
    {
        foreach (['events', 'dominions'] as $endpoint) {
            $response = $this->getJson('/api/v1/rounds/999999/' . $endpoint)
                ->assertStatus(404)
                ->assertExactJson([
                    'error' => 'not_found',
                    'message' => 'No round exists with that ID.',
                ]);

            $this->assertStringNotContainsString('OpenDominion', $response->getContent());
        }
    }

    private function createEventOfType(Round $round, Dominion $dominion, string $type): GameEvent
    {
        return GameEvent::create([
            'round_id' => $round->id,
            'source_type' => Dominion::class,
            'source_id' => $dominion->id,
            'type' => $type,
        ]);
    }
}
