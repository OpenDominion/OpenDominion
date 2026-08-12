<?php

namespace OpenDominion\Tests\Feature\Http\Api\V1;

use OpenDominion\Tests\AbstractTestCase;

class DominionApiKeyMiddlewareTest extends AbstractTestCase
{
    public function testMissingApiKeyReturns401(): void
    {
        $this->getJson('/api/v1/dominions/me')
            ->assertStatus(401)
            ->assertJson(['error' => 'missing_api_key']);
    }

    public function testUnknownApiKeyReturns401(): void
    {
        $this->withHeader('X-API-Key', 'nope-not-a-real-key')
            ->getJson('/api/v1/dominions/me')
            ->assertStatus(401)
            ->assertJson(['error' => 'invalid_api_key']);
    }

    public function testValidApiKeyReturnsMePayload(): void
    {
        $user = $this->createUser();
        $round = $this->createRound();
        $dominion = $this->createDominion($user, $round);
        $dominion->update(['api_key' => 'test-key-happy-path']);

        $this->withHeader('X-API-Key', 'test-key-happy-path')
            ->getJson('/api/v1/dominions/me')
            ->assertOk()
            ->assertJson([
                'id' => $dominion->id,
                'name' => $dominion->name,
                'realm' => ['number' => $dominion->realm->number],
                'round' => ['id' => $round->id, 'number' => $round->number],
            ]);
    }

    public function testBearerTokenFallbackWorks(): void
    {
        $user = $this->createUser();
        $round = $this->createRound();
        $dominion = $this->createDominion($user, $round);
        $dominion->update(['api_key' => 'bearer-fallback-key']);

        $this->withHeader('Authorization', 'Bearer bearer-fallback-key')
            ->getJson('/api/v1/dominions/me')
            ->assertOk()
            ->assertJsonPath('id', $dominion->id);
    }

    public function testLockedDominionReturns403(): void
    {
        $user = $this->createUser();
        $round = $this->createRound();
        $dominion = $this->createDominion($user, $round);
        $dominion->update(['api_key' => 'locked-key', 'locked_at' => now()]);

        $this->withHeader('X-API-Key', 'locked-key')
            ->getJson('/api/v1/dominions/me')
            ->assertStatus(403)
            ->assertJson(['error' => 'dominion_locked']);
    }

    public function testEndedRoundReturns410(): void
    {
        $user = $this->createUser();
        $round = $this->createRound('-30 days', '-1 day');
        $dominion = $this->createDominion($user, $round);
        $dominion->update(['api_key' => 'ended-round-key']);

        $this->withHeader('X-API-Key', 'ended-round-key')
            ->getJson('/api/v1/dominions/me')
            ->assertStatus(410)
            ->assertJson(['error' => 'round_ended']);
    }
}
