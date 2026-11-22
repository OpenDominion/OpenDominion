<?php

namespace OpenDominion\Tests\Feature\Http\Dominion;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Services\PackService;
use OpenDominion\Tests\AbstractTestCase;

class RealmAdvisorsTest extends AbstractTestCase
{
    private Round $round;
    private Realm $holdingRealm;
    private Dominion $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->round = $this->createRound('+7 days');
        $this->round->update(['mixed_alignment' => true]);
        $this->holdingRealm = Realm::create([
            'round_id' => $this->round->id,
            'number' => 0,
            'name' => 'The Graveyard',
            'alignment' => 'neutral',
        ]);
        $this->viewer = $this->createAndSelectDominion($this->createAndImpersonateUser(), $this->round, null, $this->holdingRealm);
    }

    public function testUnassignedPlayersCannotReadEachOthersAdvisorsByDefault(): void
    {
        $target = $this->createDominion($this->createUser(), $this->round, null, $this->holdingRealm);

        $this->get(route('dominion.realm.advisors.military', ['target' => $target]))
            ->assertRedirect()
            ->assertSessionHasErrors();
        $this->get(route('dominion.calculations.military', ['dominion' => $target->id]))
            ->assertOk()
            ->assertViewHas('targetInfoOps', fn ($ops) => $ops->isEmpty());
    }

    public function testUnassignedPlayersCanSeeOnlyTheirOwnAndSharedAdvisorsInTheApi(): void
    {
        $this->viewer->update(['api_key' => 'holding-realm-key']);
        $pack = app(PackService::class)->createPack($this->viewer, 'Advisor Pack', 'test-password', 3);
        $this->viewer->update(['pack_id' => $pack->id]);
        $packmate = $this->createDominion($this->createUser(), $this->round, null, $this->holdingRealm, ['pack_id' => $pack->id]);
        $this->createDominion($this->createUser(), $this->round, null, $this->holdingRealm, [
            'pack_id' => $pack->id,
            'settings' => ['realmadvisors' => [$this->viewer->id => false]],
        ]);
        $sharing = $this->createDominion($this->createUser(), $this->round, null, $this->holdingRealm, [
            'settings' => ['realmadvisors' => [$this->viewer->id => true]],
        ]);
        $this->createDominion($this->createUser(), $this->round, null, $this->holdingRealm);

        $this->withHeader('X-API-Key', 'holding-realm-key');
        $visible = $this->getJson('/api/v1/dominions/me/advisors')->assertOk()->json('dominions');
        $expected = [$this->viewer->id, $packmate->id, $sharing->id];
        $this->assertEqualsCanonicalizing($expected, array_keys($visible));

        $this->travelTo($this->round->start_date->copy()->addHour());
        $listed = collect($this->getJson('/api/v1/rounds/' . $this->round->id . '/dominions')->assertOk()->json());
        $this->assertEqualsCanonicalizing($expected, $listed->where('shares_advisors', true)->pluck('id')->all());
    }

    public function testRealmDefaultRequiresFinishedAssignmentAndANumberedRealm(): void
    {
        $target = $this->createDominion($this->createUser(), $this->round, null, $this->holdingRealm);
        $this->travelTo($this->round->realmAssignmentDate()->addHour());
        $this->round->update(['assignment_complete' => true]);

        $this->assertFalse($this->viewer->inRealmAndSharesAdvisors($target->fresh()));

        $realm = $this->createRealm($this->round);
        $this->viewer->update(['realm_id' => $realm->id]);
        $target->update(['realm_id' => $realm->id]);
        $this->round->update(['assignment_complete' => false]);
        $this->assertFalse($this->viewer->inRealmAndSharesAdvisors($target->fresh()));

        $this->round->update(['assignment_complete' => true]);
        $this->assertTrue($this->viewer->inRealmAndSharesAdvisors($target->fresh()));
    }
}
