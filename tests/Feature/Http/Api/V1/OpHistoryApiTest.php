<?php

namespace OpenDominion\Tests\Feature\Http\Api\V1;

use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Carbon;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\InfoOp;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Tests\AbstractTestCase;

class OpHistoryApiTest extends AbstractTestCase
{
    private Round $round;
    private Dominion $scout;
    private Dominion $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);

        $this->round = $this->createRound();
        $this->scout = $this->createDominion($this->createUser(), $this->round);
        $this->scout->update(['api_key' => 'scout-key', 'protection_finished' => true]);

        $this->target = $this->createDominionInRealm(99);
    }

    public function testListsEveryTypeYourRealmGatheredOnADominion(): void
    {
        $this->seedOp($this->target, $this->scout, 'clear_sight', ['land' => 250], now()->subHours(3));
        $this->seedOp($this->target, $this->scout, 'castle_spy', ['home' => 5], now()->subHour());
        $this->seedOp($this->target, $this->createDominionInRealm(98), 'barracks_spy', ['units' => 9]);

        $response = $this->history('dominion=' . $this->target->id)
            ->assertOk()
            ->assertJsonPath('dominion', $this->target->id);

        $this->assertSame(['castle_spy', 'clear_sight'], array_column($response->json('ops'), 'type'));
        $this->assertSame([
            'id' => $this->target->id,
            'name' => $this->target->name,
            'realm' => 99,
            'race' => $this->target->race->name,
        ], $response->json('ops.0.target'));
        $this->assertSame($this->scout->id, $response->json('ops.0.source.id'));
        $this->assertSame(250, $response->json('ops.1.land'));
    }

    public function testOwnRealmStaysHiddenWhileTheRoundIsRunning(): void
    {
        $this->seedOp($this->scout, $this->target, 'clear_sight', ['land' => 500]);

        foreach (['dominion=' . $this->scout->id, 'realm=' . $this->scout->realm->number] as $query) {
            $this->history($query)
                ->assertStatus(422)
                ->assertJson(['error' => 'same_realm']);
        }

        $this->history()
            ->assertOk()
            ->assertJsonPath('ops', []);
    }

    public function testAfterTheRoundListsEveryOpTakenOnOwnDominionByAnyRealm(): void
    {
        $other = $this->createDominionInRealm(98);
        $this->seedOp($this->scout, $this->target, 'barracks_spy', ['units' => 1], now()->subHours(5));
        $this->seedOp($this->scout, $other, 'barracks_spy', ['units' => 2], now()->subHours(2));
        $this->seedOp($this->scout, $this->target, 'castle_spy', ['home' => 1], now()->subHour());
        $this->endRound();

        $response = $this->history('dominion=' . $this->scout->id . '&type=barracks_spy')->assertOk();
        $sources = array_column($response->json('ops'), 'source');

        $this->assertSame([2, 1], array_column($response->json('ops'), 'units'));
        $this->assertSame([$other->id, $this->target->id], array_column($sources, 'id'));
        $this->assertSame([$other->name, $this->target->name], array_column($sources, 'name'));
        $this->assertSame([98, 99], array_column($sources, 'realm'));

        $this->history('dominion=' . $this->scout->id)
            ->assertOk()
            ->assertJsonCount(3, 'ops');
    }

    public function testAfterTheRoundListsEveryOpTakenOnTheWholeOwnRealm(): void
    {
        $realmie = $this->createDominion($this->createUser(), $this->round, $this->scout->race, $this->scout->realm);
        $other = $this->createDominionInRealm(98);
        $this->seedOp($this->scout, $this->target, 'clear_sight', ['land' => 1], now()->subDays(3));
        $this->seedOp($realmie, $other, 'survey_dominion', ['constructed' => []], now()->subDays(2));
        $this->seedOp($realmie, $this->target, 'clear_sight', ['land' => 2], now()->subDay());
        $this->seedOp($this->target, $this->scout, 'castle_spy', ['home' => 9]);
        $this->endRound();

        $ops = $this->history('realm=' . $this->scout->realm->number)->assertOk()->json('ops');

        $this->assertSame(
            [$realmie->id, $realmie->id, $this->scout->id],
            array_column(array_column($ops, 'target'), 'id')
        );
        $this->assertSame(['clear_sight', 'survey_dominion', 'clear_sight'], array_column($ops, 'type'));
        $this->assertSame(
            [$this->target->id, $other->id, $this->target->id],
            array_column(array_column($ops, 'source'), 'id')
        );
    }

    public function testCanBeNarrowedToATypeAndATimeWindow(): void
    {
        foreach ([3, 2, 1] as $daysAgo) {
            $this->seedOp($this->target, $this->scout, 'clear_sight', ['land' => $daysAgo], now()->subDays($daysAgo));
            $this->seedOp($this->target, $this->scout, 'castle_spy', ['home' => $daysAgo], now()->subDays($daysAgo));
        }

        $since = now()->subHours(60)->toIso8601ZuluString();
        $until = now()->subHours(36)->toIso8601ZuluString();
        $dominion = 'dominion=' . $this->target->id;
        $window = '&since=' . $since . '&until=' . $until;

        $this->history($dominion . '&type=clear_sight' . $window)
            ->assertOk()
            ->assertJsonPath('type', 'clear_sight')
            ->assertJsonPath('since', $since)
            ->assertJsonPath('until', $until)
            ->assertJsonCount(1, 'ops')
            ->assertJsonPath('ops.0.land', 2);

        $this->history($dominion . $window)
            ->assertOk()
            ->assertJsonCount(2, 'ops');

        $this->history($dominion . '&limit=1')
            ->assertOk()
            ->assertJsonCount(1, 'ops');
    }

    public function testRevelationCastersStayHidden(): void
    {
        $this->seedOp($this->scout, $this->target, 'revelation', [
            [
                'spell' => 'plague',
                'duration' => 9,
                'cast_by_dominion_id' => 999,
                'cast_by_dominion_name' => 'Evil Sorcerer',
                'cast_by_dominion_realm_number' => 4,
            ],
        ]);
        $this->endRound();

        $this->history('dominion=' . $this->scout->id)
            ->assertOk()
            ->assertJsonPath('ops.0.source.id', $this->target->id)
            ->assertJsonPath('ops.0.spells.0.spell', 'plague')
            ->assertJsonPath('ops.0.spells.0.cast_by_dominion_id', null)
            ->assertJsonPath('ops.0.spells.0.cast_by_dominion_name', null);
    }

    public function testOtherRealmsOpsOnOtherDominionsStayHiddenAfterTheRound(): void
    {
        $this->seedOp($this->target, $this->createDominionInRealm(98), 'clear_sight', ['land' => 777]);
        $this->seedOp($this->target, $this->scout, 'clear_sight', ['land' => 250]);
        $this->endRound();

        $response = $this->history('dominion=' . $this->target->id)->assertOk();

        $this->assertSame([250], array_column($response->json('ops'), 'land'));
    }

    public function testClairvoyanceIsNotListed(): void
    {
        $this->seedOp($this->target, $this->scout, 'clairvoyance', ['events' => []]);

        $this->history()
            ->assertOk()
            ->assertJsonPath('ops', []);
    }

    public function testRejectsInvalidParameters(): void
    {
        $queries = [
            'dominion=999999',
            'dominion=abc',
            'realm=12345',
            'dominion=' . $this->target->id . '&realm=99',
            'type=clairvoyance',
            'since=not-a-date',
            'until=not-a-date',
        ];

        foreach ($queries as $query) {
            $this->history($query)
                ->assertStatus(422)
                ->assertJson(['error' => 'invalid_parameter']);
        }
    }

    public function testProtectedDominionCannotAccessOpHistory(): void
    {
        $this->scout->update(['protection_finished' => false]);

        $this->history()
            ->assertStatus(403)
            ->assertJson(['error' => 'under_protection']);
    }

    public function testOpHistoryIsLockedBeforeRoundStarts(): void
    {
        $this->round->update(['start_date' => now()->addDays(2)]);

        $this->history()
            ->assertStatus(403)
            ->assertJson(['error' => 'round_not_started']);
    }

    private function history(string $query = '')
    {
        return $this->withHeader('X-API-Key', 'scout-key')
            ->getJson('/api/v1/dominions/me/op-history' . ($query !== '' ? '?' . $query : ''));
    }

    private function endRound(): void
    {
        $this->round->update(['end_date' => now()->subMinute()]);
    }

    private function createDominionInRealm(int $number): Dominion
    {
        $realm = Realm::create([
            'round_id' => $this->round->id,
            'alignment' => 'good',
            'number' => $number,
            'name' => 'Realm ' . $number,
        ]);

        return $this->createDominion($this->createUser(), $this->round, $this->scout->race, $realm);
    }

    private function seedOp(
        Dominion $target,
        Dominion $source,
        string $type,
        array $data,
        ?Carbon $createdAt = null
    ): InfoOp {
        $infoOp = InfoOp::create([
            'source_realm_id' => $source->realm_id,
            'source_dominion_id' => $source->id,
            'target_dominion_id' => $target->id,
            'type' => $type,
            'data' => $data,
            'latest' => true,
        ]);

        if ($createdAt !== null) {
            $infoOp->created_at = $createdAt;
            $infoOp->save();
        }

        return $infoOp;
    }
}
