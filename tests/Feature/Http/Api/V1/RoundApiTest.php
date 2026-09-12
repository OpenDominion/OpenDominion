<?php

namespace OpenDominion\Tests\Feature\Http\Api\V1;

use Carbon\Carbon;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Round;
use OpenDominion\Tests\AbstractTestCase;

class RoundApiTest extends AbstractTestCase
{
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
                'source_type' => 'test',
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

    public function testEventsRespectsSinceFilter(): void
    {
        $round = $this->createRound();

        $old = GameEvent::create([
            'round_id' => $round->id,
            'source_type' => 'test',
            'source_id' => 1,
            'type' => 'invasion',
            'data' => [],
        ]);
        $old->created_at = now()->subDays(2);
        $old->save();

        $recent = GameEvent::create([
            'round_id' => $round->id,
            'source_type' => 'test',
            'source_id' => 2,
            'type' => 'invasion',
            'data' => [],
        ]);

        $since = now()->subDay()->toIso8601String();
        $events = $this->getJson('/api/v1/rounds/' . $round->id . '/events?since=' . urlencode($since))
            ->assertOk()
            ->json();

        $ids = collect($events)->pluck('id')->all();
        $this->assertContains((string) $recent->id, $ids);
        $this->assertNotContains((string) $old->id, $ids);
    }

    public function testEventsRejectsInvalidSince(): void
    {
        $round = $this->createRound();

        $this->getJson('/api/v1/rounds/' . $round->id . '/events?since=not-a-date')
            ->assertStatus(422)
            ->assertJson(['error' => 'invalid_parameter']);
    }

    public function testNonexistentRoundReturns404(): void
    {
        $this->getJson('/api/v1/rounds/999999/events')->assertStatus(404);
        $this->getJson('/api/v1/rounds/999999/dominions')->assertStatus(404);
    }
}
