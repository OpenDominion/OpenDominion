<?php

namespace OpenDominion\Tests\Feature\Magic;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\RealmWar;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

/**
 * Lightning Strike hits the two improvements that decide invasions, harder than
 * Lightning Bolt but only for a while. The damage grows back on its own, so it
 * opens a window rather than grinding a castle down, and there is nothing on
 * record for a realmmate to repair.
 */
class LightningStrikeTest extends AbstractBrowserKitTestCase
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
        $this->target->improvement_forges = 30000;
        $this->target->improvement_walls = 50000;
        $this->target->improvement_keep = 100000;
        $this->target->improvement_science = 20000;
        $this->target->stat_total_investment = 200000;

        $this->spellActionService = $this->app->make(SpellActionService::class);
        $this->queueService = $this->app->make(QueueService::class);

        global $mockRandomChance;
        $mockRandomChance = true;
    }

    protected function declareWar(): void
    {
        RealmWar::create([
            'source_realm_id' => $this->dominion->realm_id,
            'target_realm_id' => $this->target->realm_id,
        ]);
    }

    public function testLightningStrikeRequiresAWar(): void
    {
        $this->expectException(GameException::class);
        $this->expectExceptionMessage('You cannot cast Lightning Strike outside of war');

        $this->spellActionService->castSpell($this->dominion, 'lightning_strike', $this->target);
    }

    public function testLightningStrikeHitsForgesAndWallsOnly(): void
    {
        $this->declareWar();

        $this->spellActionService->castSpell($this->dominion, 'lightning_strike', $this->target);

        $this->assertEquals(29700, $this->target->improvement_forges);
        $this->assertEquals(49500, $this->target->improvement_walls);
        $this->assertEquals(100000, $this->target->improvement_keep);
        $this->assertEquals(20000, $this->target->improvement_science);
    }

    public function testLightningStrikeDamageIsQueuedToReturn(): void
    {
        $this->declareWar();

        $this->spellActionService->castSpell($this->dominion, 'lightning_strike', $this->target);

        $this->assertEquals(300, $this->queueService->getQueueAmount('operations', $this->target, 'improvement_forges', 12));
        $this->assertEquals(500, $this->queueService->getQueueAmount('operations', $this->target, 'improvement_walls', 12));
    }

    /**
     * Temporary damage never reaches the ledger, so Repair Castle cannot
     * refund it and Desecration-style denial has nothing to work with.
     */
    public function testLightningStrikeDamageCannotBeRepaired(): void
    {
        $this->declareWar();

        $this->spellActionService->castSpell($this->dominion, 'lightning_strike', $this->target);

        $this->assertEquals(0, $this->target->improvement_damage_forges);
        $this->assertEquals(0, $this->target->improvement_damage_walls);
    }

    public function testLightningStrikeStacksAcrossCasts(): void
    {
        $this->declareWar();

        $this->spellActionService->castSpell($this->dominion, 'lightning_strike', $this->target);
        $this->spellActionService->castSpell($this->dominion, 'lightning_strike', $this->target);

        // 1% of what remains, so the second cast takes slightly less
        $this->assertEquals(29403, $this->target->improvement_forges);
        $this->assertEquals(597, $this->queueService->getQueueAmount('operations', $this->target, 'improvement_forges', 12));
    }
}
