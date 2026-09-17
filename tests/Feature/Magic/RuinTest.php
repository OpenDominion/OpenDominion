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
use OpenDominion\Tests\AbstractBrowserKitTestCase;

/**
 * Ruin slows a dominion's castle repairs to forges and walls, the improvements
 * that decide invasions, so an attacker can open a window before striking.
 */
class RuinTest extends AbstractBrowserKitTestCase
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

    /** @var Dominion Realmmate of the target, who does the repairing */
    protected $courtMage;

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

        $mageUser = $this->createUser();
        $this->courtMage = $this->createDominionWithLegacyStats(
            $mageUser,
            $this->round,
            Race::where('name', 'Human')->firstOrFail(),
            $this->target->realm
        );
        $this->courtMage->land_plain = 8000;
        $this->courtMage->resource_mana = 100000;
        $this->courtMage->save();

        $this->target->realm->magister_dominion_id = $this->courtMage->id;
        $this->target->realm->save();

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

    protected function recordDamage(): void
    {
        $this->target->improvement_walls = 48000;
        $this->target->improvement_damage_walls = 2000;
        $this->target->improvement_forges = 29000;
        $this->target->improvement_damage_forges = 1000;
        $this->target->improvement_keep = 90000;
        $this->target->improvement_damage_keep = 10000;
        $this->target->save();
    }

    public function testRuinRequiresAWar(): void
    {
        $this->expectException(GameException::class);
        $this->expectExceptionMessage('You cannot cast Ruin outside of war');

        $this->spellActionService->castSpell($this->dominion, 'ruin', $this->target);
    }

    public function testRuinLastsFourHours(): void
    {
        $this->declareWar();

        $this->spellActionService->castSpell($this->dominion, 'ruin', $this->target);

        $ruin = Spell::where('key', 'ruin')->firstOrFail();
        $activeSpell = DominionSpell::where('dominion_id', $this->target->id)
            ->where('spell_id', $ruin->id)
            ->firstOrFail();

        // Base 4 hours, extended by 2 during a one-sided war
        $this->assertEquals(6, $activeSpell->duration);
    }

    public function testRuinSlowsRepairsToForgesAndWalls(): void
    {
        $this->declareWar();
        $this->recordDamage();

        $this->spellActionService->castSpell($this->dominion, 'ruin', $this->target);
        $this->target->unsetRelation('spells');

        $result = $this->spellActionService->castSpell($this->courtMage, 'repair_castle', $this->target);

        $this->assertEquals(4, $this->queueService->getQueueAmount('operations', $this->target, 'improvement_walls', 6));
        $this->assertEquals(2, $this->queueService->getQueueAmount('operations', $this->target, 'improvement_forges', 6));
        $this->assertEquals(0, $this->queueService->getQueueAmount('operations', $this->target, 'improvement_walls', 1));
        $this->assertStringContainsString('slowed by Ruin', $result['message']);
    }

    public function testRuinLeavesEconomicRepairsAlone(): void
    {
        $this->declareWar();
        $this->recordDamage();

        $this->spellActionService->castSpell($this->dominion, 'ruin', $this->target);
        $this->target->unsetRelation('spells');

        $this->spellActionService->castSpell($this->courtMage, 'repair_castle', $this->target);

        $this->assertEquals(20, $this->queueService->getQueueAmount('operations', $this->target, 'improvement_keep', 1));
        $this->assertEquals(0, $this->queueService->getQueueAmount('operations', $this->target, 'improvement_keep', 6));
    }

    public function testRepairsAreNotSlowedWithoutRuin(): void
    {
        $this->recordDamage();

        $result = $this->spellActionService->castSpell($this->courtMage, 'repair_castle', $this->target);

        $this->assertEquals(4, $this->queueService->getQueueAmount('operations', $this->target, 'improvement_walls', 1));
        $this->assertEquals(2, $this->queueService->getQueueAmount('operations', $this->target, 'improvement_forges', 1));
        $this->assertStringNotContainsString('slowed by Ruin', $result['message']);
    }
}
