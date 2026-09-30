<?php

namespace OpenDominion\Tests\Feature\Http\Api\V1;

use Illuminate\Routing\Middleware\ThrottleRequests;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
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
            ->assertJsonCount(4, 'links')
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

    public function testMeIncludesWhitelistedResourcesProductionAndPopulation(): void
    {
        $round = $this->createRound();
        $dominion = $this->createDominion($this->createUser(), $round);
        $dominion->update([
            'api_key' => 'stats-key',
            'resource_platinum' => 123456,
            'resource_food' => 54321,
            'resource_boats' => 12.3456,
            'military_draftees' => 1500,
            'military_unit1' => 11,
            'military_unit2' => 22,
            'military_unit3' => 33,
            'military_unit4' => 44,
            'military_spies' => 55,
            'military_assassins' => 66,
            'military_wizards' => 77,
            'military_archmages' => 88,
            'spy_strength' => 87.456,
            'wizard_strength' => 100,
        ]);
        $dominion = $dominion->fresh();

        $production = app(ProductionCalculator::class);
        $population = app(PopulationCalculator::class);
        $military = app(MilitaryCalculator::class);

        $response = $this->withHeader('X-API-Key', 'stats-key')
            ->getJson('/api/v1/dominions/me')
            ->assertOk();

        $this->assertSame(
            ['id', 'name', 'realm', 'round', 'server_time', 'resources', 'military', 'hourly', 'population', 'links'],
            array_keys($response->json())
        );
        $this->assertSame([
            'platinum' => 123456,
            'food' => 54321,
            'lumber' => $dominion->resource_lumber,
            'mana' => $dominion->resource_mana,
            'ore' => $dominion->resource_ore,
            'gems' => $dominion->resource_gems,
            'tech' => $dominion->resource_tech,
            'boats' => 12.35,
        ], $response->json('resources'));

        $this->assertEquals([
            'draftees' => 1500,
            'unit1' => 11,
            'unit2' => 22,
            'unit3' => 33,
            'unit4' => 44,
            'spies' => 55,
            'assassins' => 66,
            'wizards' => 77,
            'archmages' => 88,
            'spy_strength' => 87.46,
            'wizard_strength' => 100,
            'offensive_modifier' => round(($military->getOffensivePowerMultiplier($dominion) - 1) * 100, 3),
            'defensive_modifier' => round(($military->getDefensivePowerMultiplier($dominion) - 1) * 100, 3),
        ], $response->json('military'));

        $this->assertSame(['production', 'consumption', 'decay', 'net_change'], array_keys($response->json('hourly')));
        $this->assertSame(
            ['platinum', 'food', 'lumber', 'mana', 'ore', 'gems', 'tech', 'boats'],
            array_keys($response->json('hourly.production'))
        );
        $this->assertSame($production->getPlatinumProduction($dominion), $response->json('hourly.production.platinum'));
        $this->assertSame($production->getFoodNetChange($dominion), $response->json('hourly.net_change.food'));
        $this->assertSame((int) round($production->getFoodConsumption($dominion)), $response->json('hourly.consumption.food'));
        $this->assertSame(['food', 'lumber', 'mana'], array_keys($response->json('hourly.decay')));

        $this->assertSame([
            'total' => $population->getPopulation($dominion),
            'max' => $population->getMaxPopulation($dominion),
            'peasants' => $dominion->peasants,
            'military' => $population->getPopulationMilitary($dominion),
            'jobs' => $population->getEmploymentJobs($dominion),
            'employed' => $population->getPopulationEmployed($dominion),
        ], $response->json('population'));
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
