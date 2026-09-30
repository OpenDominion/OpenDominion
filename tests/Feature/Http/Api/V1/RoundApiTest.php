<?php

namespace OpenDominion\Tests\Feature\Http\Api\V1;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Middleware\ThrottleRequests;
use OpenDominion\Mappers\GameEventMapper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Realm;
use OpenDominion\Models\RealmWar;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundWonder;
use OpenDominion\Models\Wonder;
use OpenDominion\Tests\AbstractTestCase;
use RuntimeException;

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

    public function testRoundsIndexDoesNotIncludeDayAndHour(): void
    {
        $round = $this->createRound();

        $entry = collect($this->getJson('/api/v1/rounds')->assertOk()->json())->firstWhere('id', $round->id);

        $this->assertSame(
            ['id', 'number', 'name', 'description', 'league', 'start_date', 'end_date', 'has_started', 'has_ended'],
            array_keys($entry)
        );
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
            ['id', 'name', 'race', 'realm_number', 'realm_name', 'land', 'networth', 'in_protection', 'guard'],
            array_keys($entry)
        );
    }

    public function testDominionsListShowsRoyalAndEliteGuardButNotBlackGuard(): void
    {
        $round = $this->createRound();
        $royal = $this->createDominion($this->createUser(), $round);
        $royal->update(['royal_guard_active_at' => now()->subHour()]);
        $elite = $this->createDominion($this->createUser(), $round);
        $elite->update(['royal_guard_active_at' => now()->subDay(), 'elite_guard_active_at' => now()->subHour()]);
        $applicant = $this->createDominion($this->createUser(), $round);
        $applicant->update(['royal_guard_active_at' => now()->addHours(5)]);
        $blackGuard = $this->createDominion($this->createUser(), $round);
        $blackGuard->update(['black_guard_active_at' => now()->subHour()]);

        $entries = collect($this->getJson('/api/v1/rounds/' . $round->id . '/dominions')->assertOk()->json())->keyBy('id');

        $this->assertSame('royal', $entries[$royal->id]['guard']);
        $this->assertSame('elite', $entries[$elite->id]['guard']);
        $this->assertNull($entries[$applicant->id]['guard']);
        $this->assertNull($entries[$blackGuard->id]['guard']);
    }

    public function testRealmsListsCurrentWondersAndWars(): void
    {
        $round = $this->createRound();
        $aggressors = Realm::create(['round_id' => $round->id, 'alignment' => 'good', 'number' => 3, 'name' => 'Aggressors']);
        $defenders = Realm::create(['round_id' => $round->id, 'alignment' => 'evil', 'number' => 7, 'name' => 'Defenders']);
        $bystanders = Realm::create(['round_id' => $round->id, 'alignment' => 'evil', 'number' => 9, 'name' => 'Bystanders']);

        $wonder = Wonder::where('key', 'high_clerics_tower')->firstOrFail();
        RoundWonder::create(['round_id' => $round->id, 'realm_id' => $defenders->id, 'wonder_id' => $wonder->id, 'power' => 1]);

        RealmWar::create([
            'source_realm_id' => $aggressors->id,
            'target_realm_id' => $defenders->id,
            'active_at' => now()->subHours(2),
        ]);
        RealmWar::create([
            'source_realm_id' => $bystanders->id,
            'target_realm_id' => $aggressors->id,
            'active_at' => now()->addHours(10),
        ]);
        RealmWar::create([
            'source_realm_id' => $defenders->id,
            'target_realm_id' => $bystanders->id,
            'active_at' => now()->subDays(2),
            'inactive_at' => now()->addHours(3),
        ]);
        RealmWar::create([
            'source_realm_id' => $defenders->id,
            'target_realm_id' => $aggressors->id,
            'active_at' => now()->subDays(4),
            'inactive_at' => now()->subDay(),
        ]);

        $realms = collect($this->getJson('/api/v1/rounds/' . $round->id . '/realms')->assertOk()->json())->keyBy('number');

        $this->assertSame(['number', 'name', 'wonders', 'wars'], array_keys($realms[3]));
        $this->assertSame(
            [['key' => 'high_clerics_tower', 'name' => $wonder->name, 'power' => 1, 'max_power' => 1, 'power_is_approximate' => true]],
            $realms[7]['wonders']
        );
        $this->assertSame([], $realms[3]['wonders']);

        $aggressorWars = collect($realms[3]['wars']);
        $this->assertCount(2, $aggressorWars, 'The ended war must not be listed.');
        $this->assertSame(
            ['direction', 'realm_number', 'realm_name', 'status', 'declared_at', 'active_at', 'inactive_at'],
            array_keys($aggressorWars->first())
        );
        $this->assertSame(
            ['direction' => 'outgoing', 'realm_number' => 7, 'realm_name' => 'Defenders', 'status' => 'active'],
            collect($aggressorWars->firstWhere('realm_number', 7))->only(['direction', 'realm_number', 'realm_name', 'status'])->all()
        );
        $this->assertSame(
            ['direction' => 'incoming', 'realm_number' => 9, 'status' => 'pending'],
            collect($aggressorWars->firstWhere('realm_number', 9))->only(['direction', 'realm_number', 'status'])->all()
        );

        $expiring = collect($realms[7]['wars'])->firstWhere('realm_number', 9);
        $this->assertSame('expiring', $expiring['status']);
        $this->assertMatchesRegularExpression('/^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}Z$/', $expiring['inactive_at']);
        $this->assertSame('incoming', collect($realms[9]['wars'])->firstWhere('realm_number', 7)['direction']);
    }

    public function testRealmsWonderPowerIsExactOnlyForHolderAndRealmsAtWarWithIt(): void
    {
        $round = $this->createRound();
        $holder = Realm::create(['round_id' => $round->id, 'alignment' => 'good', 'number' => 7, 'name' => 'Holders']);
        $enemy = Realm::create(['round_id' => $round->id, 'alignment' => 'good', 'number' => 3, 'name' => 'Enemies']);
        $neutral = Realm::create(['round_id' => $round->id, 'alignment' => 'good', 'number' => 9, 'name' => 'Neutrals']);

        $roundWonder = RoundWonder::create([
            'round_id' => $round->id,
            'realm_id' => $holder->id,
            'wonder_id' => Wonder::where('key', 'high_clerics_tower')->firstOrFail()->id,
            'power' => 250000,
        ]);
        $roundWonder->damage()->create([
            'realm_id' => $enemy->id,
            'dominion_id' => $this->createDominion($this->createUser(), $round, null, $enemy)->id,
            'damage' => 23456,
        ]);
        RealmWar::create(['source_realm_id' => $enemy->id, 'target_realm_id' => $holder->id, 'active_at' => now()->subHour()]);

        $keys = [];
        foreach (['holder' => $holder, 'enemy' => $enemy, 'neutral' => $neutral] as $name => $realm) {
            $dominion = $this->createDominion($this->createUser(), $round, null, $realm);
            $dominion->update(['api_key' => 'wonder-' . $name]);
            $keys[$name] = 'wonder-' . $name;
        }

        $wonderFor = function (?string $key) use ($round): array {
            $request = $key === null ? $this : $this->withHeader('X-API-Key', $key);
            $realms = collect($request->getJson('/api/v1/rounds/' . $round->id . '/realms')->assertOk()->json());

            return $realms->firstWhere('number', 7)['wonders'][0];
        };

        $exact = ['power' => 226544, 'max_power' => 250000, 'power_is_approximate' => false];
        $approximate = ['power' => 230000, 'max_power' => 250000, 'power_is_approximate' => true];

        $this->assertSame($exact, collect($wonderFor($keys['holder']))->only(array_keys($exact))->all());
        $this->assertSame($exact, collect($wonderFor($keys['enemy']))->only(array_keys($exact))->all());
        $this->assertSame($approximate, collect($wonderFor($keys['neutral']))->only(array_keys($approximate))->all());

        $this->defaultHeaders = [];
        $this->assertSame($approximate, collect($wonderFor(null))->only(array_keys($approximate))->all());
    }

    public function testRealmsRejectsAnInvalidApiKey(): void
    {
        $round = $this->createRound();

        $this->withHeader('X-API-Key', 'not-a-key')
            ->getJson('/api/v1/rounds/' . $round->id . '/realms')
            ->assertStatus(401)
            ->assertJson(['error' => 'invalid_api_key']);
    }

    public function testRealmsIsLockedBeforeRoundStarts(): void
    {
        $round = $this->createRound('+2 days', '+49 days');

        $this->getJson('/api/v1/rounds/' . $round->id . '/realms')
            ->assertStatus(403)
            ->assertJson(['error' => 'round_not_started']);
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
            'source_name' => $attacker->name,
            'source_realm_number' => $attacker->realm->number,
            'target_type' => 'dominion',
            'target_id' => $defender->id,
            'target_name' => $defender->name,
            'target_realm_number' => $defender->realm->number,
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
        $this->assertNull($entry['target_name']);
        $this->assertNull($entry['target_realm_number']);
        $this->assertStringContainsString('"data":{}', $response->getContent());
    }

    public function testEventNamesDominionsThatNoLongerAppearInSearch(): void
    {
        $round = $this->createRound();
        $dominion = $this->createDominion($this->createUser(), $round);
        $dominion->update(['abandoned_at' => now()->subHour()]);

        $event = $this->createEventOfType($round, $dominion, 'abandoned');

        $search = collect($this->getJson('/api/v1/rounds/' . $round->id . '/dominions')->assertOk()->json());
        $this->assertNull($search->firstWhere('id', $dominion->id));

        $entry = collect($this->getJson('/api/v1/rounds/' . $round->id . '/events')->assertOk()->json())
            ->firstWhere('id', (string) $event->id);

        $this->assertSame($dominion->name, $entry['source_name']);
        $this->assertSame($dominion->realm->number, $entry['source_realm_number']);
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

    public function testArrayQueryParametersAreRejectedAsJson(): void
    {
        $round = $this->createRound();

        foreach (['since[]=2026-01-01', 'type[]=invasion', 'limit[]=5', 'since[a]=x'] as $query) {
            $this->getJson('/api/v1/rounds/' . $round->id . '/events?' . $query)
                ->assertStatus(422)
                ->assertJson(['error' => 'invalid_parameter']);
        }
    }

    public function testUnexpectedErrorsAreJsonWithoutTraceEvenInDebug(): void
    {
        config(['app.debug' => true]);
        $round = $this->createRound();

        $mapper = $this->createMock(GameEventMapper::class);
        $mapper->method('getEagerLoads')->willThrowException(new RuntimeException('internal detail'));
        $this->app->instance(GameEventMapper::class, $mapper);

        $response = $this->getJson('/api/v1/rounds/' . $round->id . '/events')
            ->assertStatus(500)
            ->assertExactJson([
                'error' => 'server_error',
                'message' => 'An unexpected error occurred.',
            ]);

        $this->assertStringNotContainsString('internal detail', $response->getContent());
        $this->assertStringNotContainsString('trace', $response->getContent());
    }

    public function testDominionsListDoesNotLazyLoadPerDominion(): void
    {
        $round = $this->createRound();
        for ($i = 0; $i < 4; $i++) {
            $this->createDominion($this->createUser(), $round)->update(['protection_finished' => true]);
        }

        $lazyLoads = [];
        Model::preventLazyLoading();
        Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation) use (&$lazyLoads) {
            $lazyLoads[] = class_basename($model) . '::' . $relation;
        });

        try {
            $this->getJson('/api/v1/rounds/' . $round->id . '/dominions')->assertOk()->assertJsonCount(4);
        } finally {
            Model::preventLazyLoading(false);
            Model::handleLazyLoadingViolationUsing(null);
        }

        $this->assertSame([], $lazyLoads);
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
