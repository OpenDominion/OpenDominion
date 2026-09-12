<?php

namespace OpenDominion\Tests\Feature\Http\Api\V1;

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
            ->getJson('/api/v1/dominions/me/ops')
            ->assertOk();

        $payload = $response->json();

        $this->assertSame($this->scout->id, $payload['dominion']['id']);
        $this->assertArrayHasKey((string) $this->target->id, $payload['targets']);

        $targetPayload = $payload['targets'][(string) $this->target->id];
        $this->assertSame($this->target->id, $targetPayload['id']);
        $this->assertArrayHasKey('status', $targetPayload['ops']);
        $this->assertArrayHasKey('castle', $targetPayload['ops']);
        $this->assertSame(42, $targetPayload['ops']['status']['military_unit1']);
        $this->assertSame(5, $targetPayload['ops']['castle']['home']);
        $this->assertArrayNotHasKey('barracks', $targetPayload['ops']);
    }

    public function testMaxAgeHoursFilterExcludesStaleOps(): void
    {
        $stale = $this->seedInfoOp('clear_sight', ['land' => 250]);
        $stale->created_at = now()->subHours(3);
        $stale->save();

        $response = $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/ops?max_age_hours=1')
            ->assertOk();

        $this->assertSame([], (array) $response->json('targets'));
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
            ->getJson('/api/v1/dominions/me/ops')
            ->assertOk();

        $spells = $response->json('targets.' . $this->target->id . '.ops.revelation.spells');
        $this->assertNotEmpty($spells);
        $this->assertNull($spells[0]['cast_by_dominion_id']);
        $this->assertNull($spells[0]['cast_by_dominion_name']);
        $this->assertNull($spells[0]['cast_by_dominion_realm_number']);
    }

    public function testSingleTargetEndpointReturnsThatTarget(): void
    {
        $this->seedInfoOp('clear_sight', ['land' => 300]);

        $response = $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/ops/' . $this->target->id)
            ->assertOk();

        $this->assertSame($this->target->id, $response->json('target.id'));
        $this->assertSame(300, $response->json('target.ops.status.land'));
    }

    public function testSingleTargetEndpointReturns404WhenNoOpsExist(): void
    {
        $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/ops/' . $this->target->id)
            ->assertStatus(404)
            ->assertJson(['error' => 'not_found']);
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
            ->getJson('/api/v1/dominions/me/ops')
            ->assertOk();

        $this->assertSame([], (array) $response->json('targets'));
    }

    private function seedInfoOp(string $type, array $data): InfoOp
    {
        return InfoOp::create([
            'source_realm_id' => $this->scout->realm_id,
            'source_dominion_id' => $this->scout->id,
            'target_dominion_id' => $this->target->id,
            'type' => $type,
            'data' => $data,
            'latest' => true,
        ]);
    }
}
