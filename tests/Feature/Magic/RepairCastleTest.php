<?php

namespace OpenDominion\Tests\Feature\Magic;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\Race;
use OpenDominion\Models\RealmWar;
use OpenDominion\Models\Round;
use OpenDominion\Models\Spell;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\TickService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

/**
 * Lightning Bolt records the castle damage it deals, and Repair Castle gives a
 * realmmate a portion of it back. Damage is tracked per improvement so nothing
 * can be repaired past what was invested.
 */
class RepairCastleTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var SpellActionService */
    protected $spellActionService;

    /** @var QueueService */
    protected $queueService;

    /** @var Round */
    protected $round;

    /** @var Dominion */
    protected $dominion;

    /** @var Dominion */
    protected $target;

    /** @var Dominion */
    protected $realmmate;

    protected function setUp(): void
    {
        parent::setUp();

        $user = $this->createAndImpersonateUser();
        $this->round = $this->createRound('-4 days midnight');

        $this->dominion = $this->createDominionWithLegacyStats($user, $this->round, Race::where('name', 'Dark Elf')->firstOrFail());
        $this->dominion->land_plain = 8000;
        $this->dominion->resource_mana = 100000;
        $this->dominion->military_wizards = 5000;

        $targetUser = $this->createUser();
        $this->target = $this->createDominionWithLegacyStats($targetUser, $this->round, Race::where('name', 'Human')->firstOrFail());
        $this->target->land_plain = 8000;

        $realmmateUser = $this->createUser();
        $this->realmmate = $this->createDominionWithLegacyStats(
            $realmmateUser,
            $this->round,
            Race::where('name', 'Dark Elf')->firstOrFail(),
            $this->dominion->realm
        );

        $this->dominion->realm->magister_dominion_id = $this->dominion->id;
        $this->dominion->realm->save();

        $this->spellActionService = $this->app->make(SpellActionService::class);
        $this->queueService = $this->app->make(QueueService::class);

        global $mockRandomChance;
        $mockRandomChance = true;
    }

    protected function castLightningBolt(): void
    {
        RealmWar::create([
            'source_realm_id' => $this->dominion->realm_id,
            'target_realm_id' => $this->target->realm_id,
        ]);

        $this->target->improvement_keep = 100000;
        $this->target->improvement_walls = 50000;
        $this->target->stat_total_investment = 150000;

        $this->spellActionService->castSpell($this->dominion, 'lightning_bolt', $this->target);
    }

    public function testLightningBoltRecordsDamageForRepair(): void
    {
        $this->castLightningBolt();

        $this->assertEquals(200, $this->target->improvement_damage_keep);
        $this->assertEquals(100, $this->target->improvement_damage_walls);
        $this->assertEquals(99800, $this->target->improvement_keep);
        $this->assertEquals(49900, $this->target->improvement_walls);
    }

    public function testLightningBoltNoLongerAppliesLightningStorm(): void
    {
        $this->castLightningBolt();

        $lightningStorm = Spell::where('key', 'lightning_storm')->firstOrFail();

        $this->assertEquals(
            0,
            DominionSpell::where('dominion_id', $this->target->id)->where('spell_id', $lightningStorm->id)->count()
        );
        $this->assertEquals(0, $this->target->lightning_bolt_meter);
    }

    /**
     * Repairs are a percentage of the damage on record, so a dominion that has
     * taken more damage gets more back per cast.
     */
    public function testRepairCastleRestoresAPortionOfRecordedDamage(): void
    {
        $this->realmmate->improvement_keep = 90000;
        $this->realmmate->improvement_damage_keep = 10000;
        $this->realmmate->improvement_walls = 48000;
        $this->realmmate->improvement_damage_walls = 2000;
        $this->realmmate->save();

        $result = $this->spellActionService->castSpell($this->dominion, 'repair_castle', $this->realmmate);

        // The damage comes off the ledger immediately, so it cannot be claimed twice
        $this->assertEquals(9980, $this->realmmate->improvement_damage_keep);
        $this->assertEquals(1996, $this->realmmate->improvement_damage_walls);

        // The points themselves arrive on the next tick
        $this->assertEquals(90000, $this->realmmate->improvement_keep);
        $this->assertEquals(48000, $this->realmmate->improvement_walls);
        $this->assertEquals(20, $this->queueService->getQueueAmount('operations', $this->realmmate, 'improvement_keep', 1));
        $this->assertEquals(4, $this->queueService->getQueueAmount('operations', $this->realmmate, 'improvement_walls', 1));
        $this->assertStringContainsString('24', $result['message']);
    }

    public function testQueuedRepairsArriveOnTheNextTick(): void
    {
        $this->realmmate->protection_ticks_remaining = 0;
        $this->realmmate->improvement_keep = 90000;
        $this->realmmate->improvement_damage_keep = 10000;
        $this->realmmate->save();

        $this->spellActionService->castSpell($this->dominion, 'repair_castle', $this->realmmate);

        $this->app->make(TickService::class)->performTick($this->round);

        $this->seeInDatabase('dominions', [
            'id' => $this->realmmate->id,
            'improvement_keep' => 90020,
        ]);
    }

    public function testRepairCastleRoundsSmallDamageUp(): void
    {
        $this->realmmate->improvement_keep = 99997;
        $this->realmmate->improvement_damage_keep = 3;
        $this->realmmate->save();

        $this->spellActionService->castSpell($this->dominion, 'repair_castle', $this->realmmate);

        $this->assertEquals(1, $this->queueService->getQueueAmount('operations', $this->realmmate, 'improvement_keep', 1));
        $this->assertEquals(2, $this->realmmate->improvement_damage_keep);
    }

    public function testRepairCastleNeverExceedsRecordedDamage(): void
    {
        $this->realmmate->improvement_keep = 99999;
        $this->realmmate->improvement_damage_keep = 1;
        $this->realmmate->save();

        $this->spellActionService->castSpell($this->dominion, 'repair_castle', $this->realmmate);

        $this->assertEquals(1, $this->queueService->getQueueAmount('operations', $this->realmmate, 'improvement_keep', 1));
        $this->assertEquals(0, $this->realmmate->improvement_damage_keep);
    }

    public function testRepairCastleOnlyRepairsDamagedImprovements(): void
    {
        $this->realmmate->improvement_spires = 50000;
        $this->realmmate->improvement_harbor = 20000;
        $this->realmmate->improvement_science = 10000;
        $this->realmmate->improvement_damage_science = 4000;
        $this->realmmate->save();

        $this->spellActionService->castSpell($this->dominion, 'repair_castle', $this->realmmate);

        $this->assertEquals(8, $this->queueService->getQueueAmount('operations', $this->realmmate, 'improvement_science', 1));
        $this->assertEquals(0, $this->queueService->getQueueAmount('operations', $this->realmmate, 'improvement_spires', 1), 'Spires cannot be damaged, so they are never repaired');
        $this->assertEquals(0, $this->queueService->getQueueAmount('operations', $this->realmmate, 'improvement_harbor', 1));
    }

    public function testRepairCastleIsRejectedOnAnUndamagedCastle(): void
    {
        $manaBefore = $this->dominion->resource_mana;

        try {
            $this->spellActionService->castSpell($this->dominion, 'repair_castle', $this->realmmate);
            $this->fail('Repair Castle should be rejected when there is no damage on record');
        } catch (GameException $e) {
            $this->assertStringContainsString('castle is undamaged', $e->getMessage());
        }

        $this->assertEquals($manaBefore, $this->dominion->resource_mana, 'A rejected cast should not cost mana');
    }
}
