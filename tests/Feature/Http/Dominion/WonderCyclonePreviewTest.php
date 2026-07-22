<?php

namespace OpenDominion\Tests\Feature\Http\Dominion;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Models\RoundWonder;
use OpenDominion\Models\Wonder;
use OpenDominion\Tests\AbstractTestCase;

class WonderCyclonePreviewTest extends AbstractTestCase
{
    use DatabaseTransactions;

    public function testWondersPageIncludesCycloneDamageForEachTarget(): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('-4 days midnight');
        $dominion = $this->createAndSelectDominionWithLegacyStats($user, $round);
        $wonder = Wonder::where('key', 'urg')->firstOrFail();

        RoundWonder::create([
            'round_id' => $round->id,
            'wonder_id' => $wonder->id,
            'power' => 500000,
        ]);

        $response = $this->get(route('dominion.wonders'));

        $response
            ->assertOk()
            ->assertSee('Current Cyclone damage:')
            ->assertSee('data-cyclone-damage="75"', false)
            ->assertSee('id="cyclone-damage" aria-live="polite"', false);
    }
}
