<?php

namespace OpenDominion\Tests\Feature\Http\Dominion;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Helpers\ValuablesHelper;
use OpenDominion\Models\Race;
use OpenDominion\Models\Valuable;
use OpenDominion\Tests\AbstractTestCase;

class ValuablesPageTest extends AbstractTestCase
{
    use DatabaseTransactions;

    public function testStolenValuableShowsAFullPriceBadgeDuringTheGracePeriod(): void
    {
        $round = $this->createRound('-7 days');

        $owner = $this->createAndImpersonateUser();
        $dominion = $this->createDominion($owner, $round, Race::where('key', 'human')->firstOrFail());
        $target = $this->createDominion($this->createUser(), $round, Race::where('key', 'human')->firstOrFail());

        $valuable = new Valuable();
        $valuable->round_id = $round->id;
        $valuable->source_dominion_id = $dominion->id;
        $valuable->target_dominion_id = $target->id;
        $valuable->name = 'Freshly Stolen Trinket';
        $valuable->rarity = ValuablesHelper::RARITY_COMMON;
        $valuable->type = 'relic';
        $valuable->status = Valuable::STATUS_STOLEN;
        $valuable->required_spy_hours = 100;
        $valuable->discovered_at = now()->subHours(2);
        $valuable->stolen_at = now()->subHour();
        $valuable->save();

        $this->selectDominion($dominion->fresh());

        $max = ValuablesHelper::getRarityConfig()[ValuablesHelper::RARITY_COMMON]['base_value_max'];

        $this->get(route('dominion.valuables'))
            ->assertOk()
            ->assertSee('Freshly Stolen Trinket')
            ->assertSee('Full price')
            ->assertSee(number_format($max) . 'p');
    }

    public function testStolenValuablePastTheGracePeriodHasNoBadge(): void
    {
        $round = $this->createRound('-7 days');

        $owner = $this->createAndImpersonateUser();
        $dominion = $this->createDominion($owner, $round, Race::where('key', 'human')->firstOrFail());
        $target = $this->createDominion($this->createUser(), $round, Race::where('key', 'human')->firstOrFail());

        $valuable = new Valuable();
        $valuable->round_id = $round->id;
        $valuable->source_dominion_id = $dominion->id;
        $valuable->target_dominion_id = $target->id;
        $valuable->name = 'Aging Trinket';
        $valuable->rarity = ValuablesHelper::RARITY_COMMON;
        $valuable->type = 'relic';
        $valuable->status = Valuable::STATUS_STOLEN;
        $valuable->required_spy_hours = 100;
        $valuable->discovered_at = now()->subHours(24);
        $valuable->stolen_at = now()->subHours(ValuablesHelper::SALE_PRICE_GRACE_HOURS + 6);
        $valuable->save();

        $this->selectDominion($dominion->fresh());

        $this->get(route('dominion.valuables'))
            ->assertOk()
            ->assertSee('Aging Trinket')
            ->assertDontSee('Full price');
    }

    public function testIntelForSaleShowsTheBuyersFlooredSpyHoursWithoutPersistingThem(): void
    {
        $round = $this->createRound('-7 days');

        $seller = $this->createDominion($this->createUser(), $round, Race::where('key', 'human')->firstOrFail());

        $buyerUser = $this->createAndImpersonateUser();
        $buyer = $this->createDominion($buyerUser, $round, Race::where('key', 'human')->firstOrFail());
        $buyer->realm_id = $seller->realm_id;
        $buyer->land_plain = 4000;
        $buyer->save();

        $target = $this->createDominion($this->createUser(), $round, Race::where('key', 'human')->firstOrFail());

        $valuable = new Valuable();
        $valuable->round_id = $round->id;
        $valuable->source_dominion_id = $seller->id;
        $valuable->target_dominion_id = $target->id;
        $valuable->name = 'Trinket of Testing';
        $valuable->rarity = ValuablesHelper::RARITY_COMMON;
        $valuable->type = 'relic';
        $valuable->status = Valuable::STATUS_DISCOVERED;
        $valuable->required_spy_hours = 50;
        $valuable->discovered_at = now();
        $valuable->is_listed = true;
        $valuable->save();

        $this->selectDominion($buyer->fresh());

        $expected = app(ValuablesHelper::class)->getMinimumRequiredSpyHours($buyer->fresh(), $valuable->rarity);

        $response = $this->get(route('dominion.valuables'));

        $response->assertOk()
            ->assertSee('Trinket of Testing')
            ->assertSee(number_format($expected));

        // The floor is display-only until the intel is actually bought
        $this->assertEquals(50, $valuable->fresh()->required_spy_hours);
    }
}
