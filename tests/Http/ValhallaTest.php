<?php

namespace OpenDominion\Tests\Http;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use OpenDominion\Models\DailyRanking;
use OpenDominion\Tests\AbstractTestCase;

class ValhallaTest extends AbstractTestCase
{
    use DatabaseTransactions;

    public function testUserPageRendersForUserWithNoDominions(): void
    {
        $user = $this->createUser();

        $response = $this->get(route('valhalla.user', $user));

        $response->assertStatus(200);
        $response->assertSee('Lifetime Ranking');
        $response->assertSee('No records found.');
    }

    public function testUserPageRendersAggregatedRankingsForUserWithEndedRound(): void
    {
        $user = $this->createUser();
        $round = $this->createRound('-60 days', '-10 days');
        $dominion = $this->createDominion($user, $round);

        DailyRanking::create([
            'round_id' => $round->id,
            'dominion_id' => $dominion->id,
            'dominion_name' => $dominion->name,
            'race_name' => $dominion->race->name,
            'realm_number' => $dominion->realm->number,
            'realm_name' => $dominion->realm->name,
            'key' => 'largest-dominions',
            'value' => 1000,
            'rank' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $response = $this->get(route('valhalla.user', $user));

        $response->assertStatus(200);
        $response->assertSee('Lifetime Ranking');
        $response->assertSee('Aggregated Rankings');
        $response->assertSee('Most Decorated (Titles)');
        $response->assertSee($dominion->name);
    }

    public function testRoundPageRendersForEndedRound(): void
    {
        $round = $this->createRound('-60 days', '-10 days');
        $user = $this->createUser();
        $dominion = $this->createDominion($user, $round);

        $response = $this->get(route('valhalla.round', $round));

        $response->assertStatus(200);
        $response->assertSee($round->name);
        $response->assertSee('Round information');
        $response->assertSee('Statistics');
        $response->assertSee($dominion->race->name);
    }

    public function testRoundPageRedirectsForActiveRound(): void
    {
        $round = $this->createRound('-10 days', '+10 days');

        $response = $this->get(route('valhalla.round', $round));

        $response->assertStatus(302);
        $response->assertSessionHasErrors();
    }

    public function testRoundPageDoesNotTriggerNPlusOneOnRaceLookup(): void
    {
        $round = $this->createRound('-60 days', '-10 days');
        $userA = $this->createUser();
        $userB = $this->createUser();
        $this->createDominion($userA, $round);
        $this->createDominion($userB, $round);

        DB::enableQueryLog();
        $response = $this->get(route('valhalla.round', $round));
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $response->assertStatus(200);
        // Old implementation lazy-loaded every dominion's race relation (1 query per dominion).
        // The targeted Race::whereIn query + scalar counts should keep this well under 25.
        $this->assertLessThan(25, $queryCount, "Expected <25 queries, got {$queryCount}");
    }
}
