<?php

namespace OpenDominion\Tests\Feature\Dominion;

use OpenDominion\Http\Middleware\PreventRequestForgery;
use OpenDominion\Models\Bounty;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\InfoOp;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\BountyService;
use OpenDominion\Services\Dominion\SelectorService;
use OpenDominion\Tests\AbstractTestCase;

class BountyBoardTest extends AbstractTestCase
{
    /** @var Round */
    protected $round;

    /** @var Dominion */
    protected $dominion;

    /** @var Dominion */
    protected $realmie;

    /** @var Dominion */
    protected $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = $this->createAndImpersonateUser();
        $this->round = $this->createRound('-4 days midnight');

        $this->dominion = $this->createDominionWithLegacyStats($user, $this->round, Race::where('name', 'Halfling')->firstOrFail());
        $this->dominion->update([
            'protection_ticks_remaining' => 0,
            'military_spies' => 500,
            'land_plain' => 10000,
        ]);

        $this->realmie = $this->createDominion(
            $this->createUser(),
            $this->round,
            Race::where('name', 'Halfling')->firstOrFail(),
            $this->dominion->realm
        );

        $this->target = $this->createDominionWithLegacyStats($this->createUser(), $this->round, Race::where('name', 'Nomad')->firstOrFail());
        $this->target->update([
            'protection_ticks_remaining' => 0,
            'land_plain' => 10000,
        ]);
    }

    /**
     * @param array $attributes
     * @return Bounty
     */
    protected function createBounty(array $attributes = []): Bounty
    {
        return Bounty::create(array_merge([
            'round_id' => $this->round->id,
            'source_realm_id' => $this->dominion->realm_id,
            'source_dominion_id' => $this->realmie->id,
            'target_dominion_id' => $this->target->id,
            'type' => 'barracks_spy',
        ], $attributes));
    }

    /**
     * @param array $data
     * @return \Illuminate\Testing\TestResponse
     */
    protected function postEspionage(array $data = [])
    {
        return $this
            ->withSession([SelectorService::SESSION_NAME => $this->dominion->id])
            ->post(route('dominion.espionage'), array_merge([
                'operation' => 'barracks_spy',
                'target_dominion' => $this->target->id,
            ], $data));
    }

    public function testEspionageFromBountyBoardIsBlockedWhenBountyAlreadyCollected()
    {
        $this->createBounty(['collected_by_dominion_id' => $this->realmie->id]);

        $response = $this->postEspionage(['from_bounty_board' => 1]);

        $this->assertContains(
            'That bounty is no longer available.',
            session('errors')->getBag('default')->all()
        );
        $this->assertSame(0, InfoOp::where('target_dominion_id', $this->target->id)->count());
        $this->assertEquals(100, $this->dominion->fresh()->spy_strength);
        $response->assertRedirect();
    }

    public function testEspionageFromBountyBoardIsBlockedWhenBountyExpired()
    {
        $bounty = $this->createBounty();
        $bounty->forceFill(['created_at' => now()->subHours(13)])->save();

        $this->postEspionage(['from_bounty_board' => 1]);

        $this->assertContains(
            'That bounty is no longer available.',
            session('errors')->getBag('default')->all()
        );
        $this->assertSame(0, InfoOp::where('target_dominion_id', $this->target->id)->count());
    }

    public function testEspionageFromBountyBoardSucceedsWhenBountyIsActive()
    {
        $bounty = $this->createBounty();

        $response = $this->postEspionage(['from_bounty_board' => 1]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect(route('dominion.bounty-board'));
        $this->assertSame(1, InfoOp::where('target_dominion_id', $this->target->id)->count());
        $this->assertSame($this->dominion->id, $bounty->fresh()->collected_by_dominion_id);
    }

    public function testEspionageOutsideBountyBoardIsNotBlockedWithoutBounty()
    {
        $response = $this->postEspionage();

        $response->assertSessionDoesntHaveErrors();
        $this->assertSame(1, InfoOp::where('target_dominion_id', $this->target->id)->count());
    }

    public function testBountyForAnotherOpDoesNotCountAsAnActiveBounty()
    {
        $this->createBounty(['type' => 'castle_spy']);

        $this->postEspionage(['from_bounty_board' => 1]);

        $this->assertContains(
            'That bounty is no longer available.',
            session('errors')->getBag('default')->all()
        );
        $this->assertSame(0, InfoOp::where('target_dominion_id', $this->target->id)->count());
    }

    public function testObservationDoesNotExcuseAnAlreadyCollectedBounty()
    {
        $this->createBounty(['collected_by_dominion_id' => $this->realmie->id]);

        $realm = $this->dominion->realm;
        $realm->settings = array_merge(($realm->settings ?? []), ['observeDominionIds' => [$this->target->id]]);
        $realm->save();

        $this->assertFalse(
            app(BountyService::class)->hasActiveBounty($this->dominion->fresh(), $this->target, 'barracks_spy')
        );

        $this->postEspionage(['from_bounty_board' => 1]);

        $this->assertContains(
            'That bounty is no longer available.',
            session('errors')->getBag('default')->all()
        );
        $this->assertSame(0, InfoOp::where('target_dominion_id', $this->target->id)->count());
    }
}
