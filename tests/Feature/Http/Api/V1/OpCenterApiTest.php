<?php

namespace OpenDominion\Tests\Feature\Http\Api\V1;

use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Carbon;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\InfoOp;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use OpenDominion\Tests\AbstractTestCase;

class OpCenterApiTest extends AbstractTestCase
{
    private Round $round;
    private User $scoutUser;
    private Dominion $scout;
    private Dominion $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);

        $this->round = $this->createRound();
        $this->scoutUser = $this->createUser();
        $this->scout = $this->createDominion($this->scoutUser, $this->round);
        $this->scout->update(['api_key' => 'scout-key']);

        // Target in a different realm so we hit the cross-realm code path.
        $targetRealm = Realm::create([
            'round_id' => $this->round->id,
            'alignment' => 'good',
            'number' => 99,
            'name' => 'Target Realm',
        ]);
        $targetUser = $this->createUser();
        $this->target = $this->createDominion(
            $targetUser,
            $this->round,
            $this->scout->race,
            $targetRealm
        );
    }

    public function testBulkOpsEndpointReturnsLatestPerType(): void
    {
        $this->seedInfoOp('clear_sight', ['military_unit1' => 42, 'land' => 250]);
        $this->seedInfoOp('castle_spy', ['home' => 5, 'alchemy' => 10]);

        $response = $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/op-center')
            ->assertOk();

        $payload = $response->json();

        $this->assertSame(['generated_at', 'max_age_hours', 'dominions'], array_keys($payload));
        $this->assertArrayHasKey((string) $this->target->id, $payload['dominions']);

        $targetPayload = $payload['dominions'][(string) $this->target->id];
        $this->assertSame($this->target->id, $targetPayload['id']);
        $this->assertArrayHasKey('clear_sight', $targetPayload['ops']);
        $this->assertArrayHasKey('castle_spy', $targetPayload['ops']);
        $this->assertSame(42, $targetPayload['ops']['clear_sight']['military_unit1']);
        $this->assertSame(5, $targetPayload['ops']['castle_spy']['home']);
        $this->assertMatchesRegularExpression('/^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}Z$/', $payload['generated_at']);
        $this->assertMatchesRegularExpression('/^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}Z$/', $targetPayload['ops']['clear_sight']['created_at']);
        $this->assertSame(
            ['clear_sight', 'revelation', 'castle_spy', 'barracks_spy', 'survey_dominion', 'land_spy', 'vision', 'disclosure'],
            array_keys($targetPayload['ops'])
        );
        foreach (['revelation', 'barracks_spy', 'survey_dominion', 'land_spy', 'vision', 'disclosure'] as $missing) {
            $this->assertNull($targetPayload['ops'][$missing]);
        }
    }

    public function testOpsGatheredByRealmiesAreIncluded(): void
    {
        $realmie = $this->createDominion($this->createUser(), $this->round, $this->scout->race, $this->scout->realm);

        InfoOp::create([
            'source_realm_id' => $this->scout->realm_id,
            'source_dominion_id' => $realmie->id,
            'target_dominion_id' => $this->target->id,
            'type' => 'clear_sight',
            'data' => ['land' => 321],
            'latest' => true,
        ]);

        $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/op-center')
            ->assertOk()
            ->assertJsonPath('dominions.' . $this->target->id . '.ops.clear_sight.land', 321);

        $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/op-center/' . $this->target->id)
            ->assertOk()
            ->assertJsonPath('dominion.ops.clear_sight.land', 321);
    }

    public function testOldOpsPathNoLongerExists(): void
    {
        $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/ops')
            ->assertStatus(404);
    }

    public function testMaxAgeHoursFilterExcludesStaleOps(): void
    {
        $stale = $this->seedInfoOp('clear_sight', ['land' => 250]);
        $stale->created_at = now()->subHours(3);
        $stale->save();

        $response = $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/op-center?max_age_hours=1')
            ->assertOk();

        $this->assertSame([], (array) $response->json('dominions'));
    }

    public function testRevelationSpellsAreObfuscated(): void
    {
        $this->seedInfoOp('revelation', [
            [
                'spell' => 'harmony',
                'duration' => 12,
                'cast_by_dominion_id' => 999,
                'cast_by_dominion_name' => 'Evil Sorcerer',
                'cast_by_dominion_realm_number' => 4,
            ],
        ]);

        $response = $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/op-center')
            ->assertOk();

        $spells = $response->json('dominions.' . $this->target->id . '.ops.revelation.spells');
        $this->assertNotEmpty($spells);
        $this->assertNull($spells[0]['cast_by_dominion_id']);
        $this->assertNull($spells[0]['cast_by_dominion_name']);
        $this->assertNull($spells[0]['cast_by_dominion_realm_number']);
    }

    public function testSingleTargetEndpointReturnsThatTarget(): void
    {
        $this->seedInfoOp('clear_sight', ['land' => 300]);

        $response = $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/op-center/' . $this->target->id)
            ->assertOk();

        $this->assertSame($this->target->id, $response->json('dominion.id'));
        $this->assertSame(['generated_at', 'max_age_hours', 'dominion'], array_keys($response->json()));
        $this->assertSame(300, $response->json('dominion.ops.clear_sight.land'));
        $this->assertCount(8, $response->json('dominion.ops'));
        $this->assertNull($response->json('dominion.ops.castle_spy'));
    }

    public function testSingleTargetEndpointReturns404WhenNoOpsExist(): void
    {
        $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/op-center/' . $this->target->id)
            ->assertStatus(404)
            ->assertJson(['error' => 'not_found']);
    }

    public function testListEndpointDefaultsToTwelveHourMaxAge(): void
    {
        $this->seedInfoOp('clear_sight', ['land' => 250], now()->subHours(13));

        $response = $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/op-center')
            ->assertOk()
            ->assertJsonPath('max_age_hours', 12);

        $this->assertSame([], (array) $response->json('dominions'));

        $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/op-center?max_age_hours=0')
            ->assertOk()
            ->assertJsonPath('max_age_hours', 0)
            ->assertJsonPath('dominions.' . $this->target->id . '.ops.clear_sight.land', 250);
    }

    public function testSingleTargetEndpointHasNoAgeLimitByDefault(): void
    {
        $this->seedInfoOp('clear_sight', ['land' => 250], now()->subDays(10));

        $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/op-center/' . $this->target->id)
            ->assertOk()
            ->assertJsonPath('max_age_hours', 0)
            ->assertJsonPath('dominion.ops.clear_sight.land', 250);

        $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/op-center/' . $this->target->id . '?max_age_hours=24')
            ->assertStatus(404);
    }

    public function testSingleTargetEndpointOnlyReturnsLatestOfEachType(): void
    {
        $this->seedInfoOp('barracks_spy', ['units' => 1], now()->subHours(5), false);
        $this->seedInfoOp('barracks_spy', ['units' => 2], now()->subHours(1), true);

        $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/op-center/' . $this->target->id)
            ->assertOk()
            ->assertJsonPath('dominion.ops.barracks_spy.units', 2);
    }

    public function testHistoryEndpointReturnsEveryOpOfTypeNewestFirst(): void
    {
        $oldest = $this->seedInfoOp('barracks_spy', ['units' => 1], now()->subDays(3), false);
        $middle = $this->seedInfoOp('barracks_spy', ['units' => 2], now()->subHours(5), false);
        $latest = $this->seedInfoOp('barracks_spy', ['units' => 3], now()->subHours(1), true);
        $this->seedInfoOp('castle_spy', ['home' => 9]);

        $response = $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/op-center/' . $this->target->id . '/barracks_spy')
            ->assertOk();

        $this->assertSame(
            ['generated_at', 'max_age_hours', 'dominion', 'type', 'ops'],
            array_keys($response->json())
        );
        $this->assertSame(0, $response->json('max_age_hours'));
        $this->assertSame('barracks_spy', $response->json('type'));
        $this->assertSame(
            [
                'id' => $this->target->id,
                'name' => $this->target->name,
                'realm' => 99,
                'race' => $this->target->race->name,
            ],
            $response->json('dominion')
        );
        $this->assertSame([3, 2, 1], array_column($response->json('ops'), 'units'));
        $this->assertSame(
            [
                $latest->created_at->toIso8601ZuluString(),
                $middle->created_at->toIso8601ZuluString(),
                $oldest->created_at->toIso8601ZuluString(),
            ],
            array_column($response->json('ops'), 'created_at')
        );
    }

    public function testHistoryEndpointRespectsMaxAgeAndLimit(): void
    {
        $this->seedInfoOp('barracks_spy', ['units' => 1], now()->subDays(3), false);
        $this->seedInfoOp('barracks_spy', ['units' => 2], now()->subHours(5), false);
        $this->seedInfoOp('barracks_spy', ['units' => 3], now()->subHours(1), true);

        $aged = $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/op-center/' . $this->target->id . '/barracks_spy?max_age_hours=12')
            ->assertOk()
            ->assertJsonPath('max_age_hours', 12);
        $this->assertSame([3, 2], array_column($aged->json('ops'), 'units'));

        $limited = $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/op-center/' . $this->target->id . '/barracks_spy?limit=1')
            ->assertOk();
        $this->assertSame([3], array_column($limited->json('ops'), 'units'));
    }

    public function testHistoryEndpointFormatsStatusAndObfuscatesRevelation(): void
    {
        $this->seedInfoOp('clear_sight', ['land' => 250, 'race_id' => 1]);
        $this->seedInfoOp('revelation', [
            [
                'spell' => 'harmony',
                'duration' => 12,
                'cast_by_dominion_id' => 999,
                'cast_by_dominion_name' => 'Evil Sorcerer',
                'cast_by_dominion_realm_number' => 4,
            ],
        ]);

        $status = $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/op-center/' . $this->target->id . '/clear_sight')
            ->assertOk()
            ->json('ops.0');
        $this->assertSame($this->target->name, $status['name']);
        $this->assertSame(99, $status['realm']);
        $this->assertArrayNotHasKey('race_id', $status);

        $response = $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/op-center/' . $this->target->id . '/revelation')
            ->assertOk();
        $this->assertSame('harmony', $response->json('ops.0.spells.0.spell'));
        $this->assertNull($response->json('ops.0.spells.0.cast_by_dominion_id'));
        $this->assertStringNotContainsString('Evil Sorcerer', $response->getContent());
    }

    public function testHistoryEndpointReturnsEmptyListWhenRealmHasNoOpsOfType(): void
    {
        $this->seedInfoOp('castle_spy', ['home' => 9]);
        InfoOp::create([
            'source_realm_id' => $this->target->realm_id,
            'source_dominion_id' => $this->target->id,
            'target_dominion_id' => $this->target->id,
            'type' => 'barracks_spy',
            'data' => ['units' => 777],
            'latest' => true,
        ]);

        $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/op-center/' . $this->target->id . '/barracks_spy')
            ->assertOk()
            ->assertExactJson([
                'generated_at' => now()->toIso8601ZuluString(),
                'max_age_hours' => 0,
                'dominion' => [
                    'id' => $this->target->id,
                    'name' => $this->target->name,
                    'realm' => 99,
                    'race' => $this->target->race->name,
                ],
                'type' => 'barracks_spy',
                'ops' => [],
            ]);
    }

    public function testHistoryEndpointRejectsUnknownType(): void
    {
        foreach (['barracks', 'status', 'clairvoyance', 'nonsense'] as $type) {
            $this->withHeader('X-API-Key', 'scout-key')
                ->getJson('/api/v1/dominions/me/op-center/' . $this->target->id . '/' . $type)
                ->assertStatus(422)
                ->assertJson(['error' => 'invalid_parameter']);
        }
    }

    public function testHistoryEndpointReturns404ForDominionInAnotherRound(): void
    {
        $otherRound = $this->createRound();
        $outsider = $this->createDominion($this->createUser(), $otherRound);

        $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/op-center/' . $outsider->id . '/barracks_spy')
            ->assertStatus(404)
            ->assertJson(['error' => 'not_found']);
    }

    public function testOpCenterEndpointsAreLockedBeforeRoundStarts(): void
    {
        $this->seedInfoOp('clear_sight', ['land' => 250]);
        $this->round->update(['start_date' => now()->addDays(2)]);

        $paths = [
            '/api/v1/dominions/me/op-center',
            '/api/v1/dominions/me/op-center/' . $this->target->id,
            '/api/v1/dominions/me/op-center/' . $this->target->id . '/clear_sight',
        ];

        foreach ($paths as $path) {
            $this->withHeader('X-API-Key', 'scout-key')
                ->getJson($path)
                ->assertStatus(403)
                ->assertExactJson([
                    'error' => 'round_not_started',
                    'message' => 'This endpoint is not available until the round has started.',
                ]);
        }
    }

    public function testMeEndpointStillWorksBeforeRoundStarts(): void
    {
        $this->round->update(['start_date' => now()->addDays(2)]);

        $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me')
            ->assertOk()
            ->assertJsonPath('id', $this->scout->id);
    }

    public function testOpCenterStillRequiresApiKeyBeforeRoundStarts(): void
    {
        $this->round->update(['start_date' => now()->addDays(2)]);

        $this->getJson('/api/v1/dominions/me/op-center')
            ->assertStatus(401)
            ->assertJson(['error' => 'missing_api_key']);
    }

    public function testNonexistentTargetDominionReturns404InApiFormat(): void
    {
        foreach (['/api/v1/dominions/me/op-center/999999', '/api/v1/dominions/me/op-center/999999/clear_sight'] as $path) {
            $response = $this->withHeader('X-API-Key', 'scout-key')
                ->getJson($path)
                ->assertStatus(404)
                ->assertExactJson([
                    'error' => 'not_found',
                    'message' => 'No dominion exists with that ID.',
                ]);

            $this->assertStringNotContainsString('OpenDominion', $response->getContent());
        }
    }

    public function testOwnDominionReturnsCurrentAdvisorData(): void
    {
        $response = $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/op-center/' . $this->scout->id)
            ->assertOk();

        $this->assertSame($this->scout->id, $response->json('dominion.id'));
        $this->assertSame(
            ['clear_sight', 'revelation', 'castle_spy', 'barracks_spy', 'survey_dominion', 'land_spy', 'vision', 'disclosure'],
            array_keys($response->json('dominion.ops'))
        );
        foreach ($response->json('dominion.ops') as $type => $op) {
            $this->assertNotNull($op, $type . ' should come from advisors');
            $this->assertSame($response->json('generated_at'), $op['created_at']);
        }
        $this->assertSame($this->scout->name, $response->json('dominion.ops.clear_sight.name'));
        $this->assertArrayHasKey('techs', $response->json('dominion.ops.vision'));
        $this->assertArrayHasKey('spells', $response->json('dominion.ops.revelation'));
    }

    public function testRealmieSharingAdvisorsReturnsCurrentDataNotInfoOps(): void
    {
        $realmie = $this->createDominion($this->createUser(), $this->round, $this->scout->race, $this->scout->realm);
        $realmie->settings = ['realmadvisors' => [$this->scout->id => true]];
        $realmie->save();
        InfoOp::create([
            'source_realm_id' => $this->scout->realm_id,
            'source_dominion_id' => $this->scout->id,
            'target_dominion_id' => $realmie->id,
            'type' => 'clear_sight',
            'data' => ['name' => 'Stale Snapshot'],
            'latest' => true,
        ]);

        $response = $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/op-center/' . $realmie->id)
            ->assertOk();

        $this->assertSame($realmie->name, $response->json('dominion.ops.clear_sight.name'));
        $this->assertNotNull($response->json('dominion.ops.barracks_spy'));
    }

    public function testRealmieNotSharingAdvisorsReturns403(): void
    {
        $realmie = $this->createDominion($this->createUser(), $this->round, $this->scout->race, $this->scout->realm);
        $realmie->settings = ['realmadvisors' => [$this->scout->id => false]];
        $realmie->save();

        $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/op-center/' . $realmie->id)
            ->assertStatus(403)
            ->assertExactJson([
                'error' => 'advisors_not_shared',
                'message' => 'This dominion has opted not to share their advisors with you.',
            ]);
    }

    public function testOpArchiveRejectsDominionsInOwnRealm(): void
    {
        $realmie = $this->createDominion($this->createUser(), $this->round, $this->scout->race, $this->scout->realm);

        foreach ([$this->scout->id, $realmie->id] as $dominionId) {
            $this->withHeader('X-API-Key', 'scout-key')
                ->getJson('/api/v1/dominions/me/op-center/' . $dominionId . '/barracks_spy')
                ->assertStatus(422)
                ->assertJson(['error' => 'same_realm']);
        }
    }

    public function testOpCenterListStillOnlyContainsInfoOps(): void
    {
        $response = $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/op-center')
            ->assertOk();

        $this->assertArrayNotHasKey((string) $this->scout->id, (array) $response->json('dominions'));
    }

    public function testOtherRealmsOpsAreNotIncluded(): void
    {
        // Op sourced from a different realm should not appear.
        $otherRealm = Realm::create([
            'round_id' => $this->round->id,
            'alignment' => 'good',
            'number' => 88,
            'name' => 'Other Scout Realm',
        ]);
        InfoOp::create([
            'source_realm_id' => $otherRealm->id,
            'source_dominion_id' => $this->target->id,
            'target_dominion_id' => $this->target->id,
            'type' => 'clear_sight',
            'data' => ['land' => 999],
            'latest' => true,
        ]);

        $response = $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/op-center')
            ->assertOk();

        $this->assertSame([], (array) $response->json('dominions'));
    }

    private function seedInfoOp(string $type, array $data, ?Carbon $createdAt = null, bool $latest = true): InfoOp
    {
        $infoOp = InfoOp::create([
            'source_realm_id' => $this->scout->realm_id,
            'source_dominion_id' => $this->scout->id,
            'target_dominion_id' => $this->target->id,
            'type' => $type,
            'data' => $data,
            'latest' => $latest,
        ]);

        if ($createdAt !== null) {
            $infoOp->created_at = $createdAt;
            $infoOp->save();
        }

        return $infoOp;
    }
}
