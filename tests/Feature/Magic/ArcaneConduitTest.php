<?php

namespace OpenDominion\Tests\Feature\Magic;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\Race;
use OpenDominion\Models\RealmWar;
use OpenDominion\Models\Round;
use OpenDominion\Models\Spell;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

/**
 * Arcane Conduit lets a realmmate lend their power to whoever can actually reach the
 * target. It doubles one instant offensive cast and then fades, so a realm's
 * giant killer does the work of two dominions only as often as someone keeps
 * feeding them.
 */
class ArcaneConduitTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var SpellActionService */
    protected $spellActionService;

    /** @var Round */
    protected $round;

    /** @var Dominion The realm's giant killer */
    protected $champion;

    /** @var Dominion */
    protected $supporter;

    /** @var Dominion */
    protected $target;

    protected function setUp(): void
    {
        parent::setUp();

        $user = $this->createAndImpersonateUser();
        $this->round = $this->createRound('-4 days midnight');

        $this->champion = $this->createDominionWithLegacyStats($user, $this->round, Race::where('name', 'Dark Elf')->firstOrFail());
        $this->champion->land_plain = 8000;
        $this->champion->resource_mana = 200000;
        $this->champion->military_wizards = 5000;

        $this->supporter = $this->createDominionWithLegacyStats(
            $this->createUser(null, ['email' => uniqid('supporter', true) . '@example.com']),
            $this->round,
            Race::where('name', 'Dark Elf')->firstOrFail(),
            $this->champion->realm
        );
        $this->supporter->land_plain = 8000;
        $this->supporter->resource_mana = 200000;
        $this->supporter->save();

        $this->target = $this->createDominionWithLegacyStats(
            $this->createUser(null, ['email' => uniqid('target', true) . '@example.com']),
            $this->round,
            Race::where('name', 'Human')->firstOrFail()
        );
        $this->target->land_plain = 8000;
        $this->target->peasants = 40000;
        $this->target->save();

        RealmWar::create([
            'source_realm_id' => $this->champion->realm_id,
            'target_realm_id' => $this->target->realm_id,
        ]);

        $this->spellActionService = $this->app->make(SpellActionService::class);

        global $mockRandomChance;
        $mockRandomChance = true;
    }

    protected function empowerChampion(): void
    {
        $this->spellActionService->castSpell($this->supporter, 'arcane_conduit', $this->champion);
        $this->champion->unsetRelation('spells');
    }

    public function testArcaneConduitDoublesTheNextInstantSpell(): void
    {
        $this->empowerChampion();

        $result = $this->spellActionService->castSpell($this->champion, 'fireball', $this->target);

        // 2.5% of 40,000 peasants, doubled
        $this->assertEquals(38000, $this->target->peasants);
        $this->assertStringContainsString('Arcane Conduit carried the cast', $result['message']);
    }

    public function testArcaneConduitIsConsumedByTheCastItCarries(): void
    {
        $this->empowerChampion();

        $this->spellActionService->castSpell($this->champion, 'fireball', $this->target);
        $this->champion->unsetRelation('spells');
        $this->spellActionService->castSpell($this->champion, 'fireball', $this->target);

        // 40,000 less 2,000 doubled, then 2.5% of what remains
        $this->assertEquals(37050, $this->target->peasants);
        $this->assertEquals(
            0,
            DominionSpell::where('dominion_id', $this->champion->id)
                ->where('spell_id', Spell::where('key', 'arcane_conduit')->firstOrFail()->id)
                ->count()
        );
    }

    /**
     * A smaller dominion lending power to a peer of the target is the point:
     * half size and doubled comes out at a full strength cast.
     */
    public function testArcaneConduitOffsetsTheCastersSizePenalty(): void
    {
        $this->champion->land_plain = 4000;
        $this->champion->save();

        $this->empowerChampion();

        $this->spellActionService->castSpell($this->champion, 'fireball', $this->target);

        $killed = 40000 - $this->target->peasants;

        $this->assertGreaterThan(950, $killed);
        $this->assertLessThan(1050, $killed);
    }

    public function testArcaneConduitDoesNotCarryDurationSpells(): void
    {
        $this->empowerChampion();

        $this->spellActionService->castSpell($this->champion, 'plague', $this->target);
        $this->champion->unsetRelation('spells');

        $this->assertEquals(
            1,
            DominionSpell::where('dominion_id', $this->champion->id)
                ->where('spell_id', Spell::where('key', 'arcane_conduit')->firstOrFail()->id)
                ->count(),
            'A duration spell should leave the empowerment untouched'
        );
    }

    public function testArcaneConduitSurvivesARepelledCast(): void
    {
        global $mockRandomChance;
        $mockRandomChance = false;

        $this->empowerChampion();
        $this->target->military_wizards = 50000;

        $this->spellActionService->castSpell($this->champion, 'fireball', $this->target);
        $this->champion->unsetRelation('spells');

        $this->assertEquals(
            1,
            DominionSpell::where('dominion_id', $this->champion->id)
                ->where('spell_id', Spell::where('key', 'arcane_conduit')->firstOrFail()->id)
                ->count(),
            'A repelled cast should not spend the empowerment'
        );
    }
}
