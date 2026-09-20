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
 * Instant war spells take a share of what the target owns while costing a share
 * of what the caster owns, so striking upward is otherwise free damage. Damage
 * now scales with the caster's size, and the realm's Warmage is the appointed
 * exception.
 */
class OffensiveSizeScalingTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var SpellActionService */
    protected $spellActionService;

    /** @var Round */
    protected $round;

    /** @var Dominion */
    protected $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAndImpersonateUser();
        $this->round = $this->createRound('-4 days midnight');

        $this->target = $this->createDominionWithLegacyStats(
            $this->createUser(null, ['email' => uniqid('target', true) . '@example.com']),
            $this->round,
            Race::where('name', 'Human')->firstOrFail()
        );
        $this->target->land_plain = 8000;
        $this->target->peasants = 40000;
        $this->target->save();

        $this->spellActionService = $this->app->make(SpellActionService::class);

        global $mockRandomChance;
        $mockRandomChance = true;
    }

    protected function createAttacker(int $land): Dominion
    {
        $dominion = $this->createDominionWithLegacyStats(
            $this->createUser(null, ['email' => uniqid('attacker', true) . '@example.com']),
            $this->round,
            Race::where('name', 'Dark Elf')->firstOrFail()
        );

        $dominion->land_plain = $land;
        $dominion->resource_mana = 200000;
        $dominion->military_wizards = 5000;
        $dominion->save();

        RealmWar::firstOrCreate([
            'source_realm_id' => $dominion->realm_id,
            'target_realm_id' => $this->target->realm_id,
        ]);

        return $dominion;
    }

    public function testAPeerDealsFullDamage(): void
    {
        $peer = $this->createAttacker(8000);

        $this->spellActionService->castSpell($peer, 'fireball', $this->target);

        // 5% of 40,000 peasants
        $this->assertEquals(38000, $this->target->peasants);
    }

    public function testASmallerCasterDealsProportionallyLess(): void
    {
        $smaller = $this->createAttacker(4000);

        $this->spellActionService->castSpell($smaller, 'fireball', $this->target);

        $killed = 40000 - $this->target->peasants;

        // Half the size, so roughly half of a peer's 2,000 peasants
        $this->assertGreaterThan(900, $killed);
        $this->assertLessThan(1100, $killed);
    }

    public function testALargerCasterGainsNothing(): void
    {
        $bigger = $this->createAttacker(16000);

        $this->spellActionService->castSpell($bigger, 'fireball', $this->target);

        $this->assertEquals(38000, $this->target->peasants);
    }

    public function testTheWarmageIgnoresTheSizeGap(): void
    {
        $warmage = $this->createAttacker(4000);
        $warmage->realm->mage_dominion_id = $warmage->id;
        $warmage->realm->save();
        $warmage->unsetRelation('realm');

        $this->spellActionService->castSpell($warmage, 'fireball', $this->target);

        $this->assertEquals(38000, $this->target->peasants);
    }

    public function testScalingAppliesToEveryInstantWarSpell(): void
    {
        $smaller = $this->createAttacker(4000);
        $this->target->resource_mana = 200000;

        $this->spellActionService->castSpell($smaller, 'mana_burn', $this->target);

        $burned = 200000 - $this->target->resource_mana;

        // 5% of 200,000 is 10,000 at full size, about half of that at half size
        $this->assertGreaterThan(4500, $burned);
        $this->assertLessThan(5500, $burned);
    }

    /**
     * Break Ward strips whole hours, so there is nothing to scale.
     */
    public function testBreakWardIsUnaffectedBySize(): void
    {
        $smaller = $this->createAttacker(4000);

        DominionSpell::create([
            'dominion_id' => $this->target->id,
            'spell_id' => Spell::where('key', 'magic_ward')->firstOrFail()->id,
            'duration' => 24,
            'cast_by_dominion_id' => $this->target->id,
        ]);
        $this->target->unsetRelation('spells');

        $this->spellActionService->castSpell($smaller, 'break_ward', $this->target);

        $this->assertEquals(
            22,
            DominionSpell::where('dominion_id', $this->target->id)->firstOrFail()->duration
        );
    }
}
