<?php

namespace OpenDominion\Tests\Feature\Http\Api\V1;

use Illuminate\Routing\Middleware\ThrottleRequests;
use OpenDominion\Tests\AbstractTestCase;

class DominionApiKeyMiddlewareTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
    }

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
                'round' => [
                    'id' => $round->id,
                    'number' => $round->number,
                    'name' => $round->name,
                    'start_date' => $round->start_date->toIso8601ZuluString(),
                    'end_date' => $round->end_date->toIso8601ZuluString(),
                ],
                'links' => [
                    'realm' => url('/api/v1/dominions/me/realm'),
                    'op_center' => url('/api/v1/dominions/me/op-center'),
                    'rounds' => url('/api/v1/rounds'),
                    'round_dominions' => url('/api/v1/rounds/' . $round->id . '/dominions'),
                    'round_realms' => url('/api/v1/rounds/' . $round->id . '/realms'),
                    'round_events' => url('/api/v1/rounds/' . $round->id . '/events'),
                ],
            ])
            ->assertJsonMissingPath('round.ends_at')
            ->assertJsonPath('round.day', $round->daysInRound())
            ->assertJsonPath('round.hour', $round->hoursInDay())
            ->assertJsonPath('round.duration_days', $round->durationInDays())
            ->assertJsonPath('server_time', fn (string $time) => preg_match('/^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}Z$/', $time) === 1)
            ->assertJsonCount(6, 'links')
            ->assertJsonPath('round.start_date', fn (string $date) => preg_match('/^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}Z$/', $date) === 1);
    }

    public function testMeDayAndHourMatchTheGameFooter(): void
    {
        $this->travelTo(now()->startOfHour()->addMinutes(20));
        $round = $this->createRound('-3 days -5 hours', '+40 days');
        $dominion = $this->createDominion($this->createUser(), $round);
        $dominion->update(['api_key' => 'clock-key']);

        $this->withHeader('X-API-Key', 'clock-key')
            ->getJson('/api/v1/dominions/me')
            ->assertOk()
            ->assertJsonPath('round.day', 4)
            ->assertJsonPath('round.hour', 6)
            ->assertJsonPath('round.duration_days', $round->durationInDays());
    }

    public function testMeDayAndHourAreNullBeforeRoundStarts(): void
    {
        $round = $this->createRound('+2 days', '+49 days');
        $dominion = $this->createDominion($this->createUser(), $round);
        $dominion->update(['api_key' => 'early-key']);

        $this->withHeader('X-API-Key', 'early-key')
            ->getJson('/api/v1/dominions/me')
            ->assertOk()
            ->assertJsonPath('round.day', null)
            ->assertJsonPath('round.hour', null)
            ->assertJsonPath('round.duration_days', 47);
    }

    public function testMeOnlyContainsIdentityAndLinks(): void
    {
        $dominion = $this->createDominion($this->createUser(), $this->createRound());
        $dominion->update(['api_key' => 'slim-key']);

        $response = $this->withHeader('X-API-Key', 'slim-key')
            ->getJson('/api/v1/dominions/me')
            ->assertOk();

        $this->assertSame(['id', 'name', 'realm', 'round', 'server_time', 'links'], array_keys($response->json()));
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

    public function testAbandonedDominionReturns403(): void
    {
        $user = $this->createUser();
        $round = $this->createRound();
        $dominion = $this->createDominion($user, $round);
        $dominion->update(['api_key' => 'abandoned-key', 'abandoned_at' => now()->subHour()]);

        $this->withHeader('X-API-Key', 'abandoned-key')
            ->getJson('/api/v1/dominions/me')
            ->assertStatus(403)
            ->assertJson(['error' => 'dominion_locked']);
    }

    public function testPendingAbandonmentCanStillAccessApi(): void
    {
        $user = $this->createUser();
        $round = $this->createRound();
        $dominion = $this->createDominion($user, $round);
        $dominion->update(['api_key' => 'pending-abandon-key', 'abandoned_at' => now()->addHours(12)]);

        $this->withHeader('X-API-Key', 'pending-abandon-key')
            ->getJson('/api/v1/dominions/me')
            ->assertOk()
            ->assertJsonPath('id', $dominion->id);
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
