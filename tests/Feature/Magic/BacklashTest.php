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
use OpenDominion\Services\Dominion\TickService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

/**
 * Resolve builds as instant spells land on a dominion, and Backlash turns it
 * into damage the caster suffers for continuing. It spares the target nothing:
 * the attack still lands in full, it just costs the attacker as well.
 */
class BacklashTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var SpellActionService */
    protected $spellActionService;

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
        $this->dominion->resource_mana = 200000;
        $this->dominion->military_wizards = 5000;
        $this->dominion->peasants = 40000;

        $targetUser = $this->createUser();
        $this->target = $this->createDominionWithLegacyStats($targetUser, $this->round, Race::where('name', 'Human')->firstOrFail());
        $this->target->land_plain = 8000;
        $this->target->resource_mana = 200000;
        $this->target->peasants = 40000;

        $this->spellActionService = $this->app->make(SpellActionService::class);

        global $mockRandomChance;
        $mockRandomChance = true;
    }

    protected function declareWar(bool $mutual = false): void
    {
        RealmWar::create([
            'source_realm_id' => $this->dominion->realm_id,
            'target_realm_id' => $this->target->realm_id,
        ]);

        if ($mutual) {
            RealmWar::create([
                'source_realm_id' => $this->target->realm_id,
                'target_realm_id' => $this->dominion->realm_id,
            ]);
        }
    }

    protected function activateBacklash(): void
    {
        DominionSpell::create([
            'dominion_id' => $this->target->id,
            'spell_id' => Spell::where('key', 'backlash')->firstOrFail()->id,
            'duration' => 12,
            'cast_by_dominion_id' => $this->target->id,
        ]);

        $this->target->unsetRelation('spells');
    }

    public function testResolveBuildsAsInstantSpellsLand(): void
    {
        $this->declareWar();

        $this->spellActionService->castSpell($this->dominion, 'fireball', $this->target);

        $this->assertEquals(10, $this->target->resolve);
    }

    /**
     * A war both realms declared is a war, not a grief.
     */
    public function testMutualWarBuildsResolveMoreSlowly(): void
    {
        $this->declareWar(true);

        $this->spellActionService->castSpell($this->dominion, 'fireball', $this->target);

        $this->assertEquals(5, $this->target->resolve);
    }

    public function testBacklashCannotBeCastBelowTheThreshold(): void
    {
        $this->target->resolve = 249;
        $this->target->save();

        $this->expectException(GameException::class);
        $this->expectExceptionMessage('needs 250 resolve');

        $this->spellActionService->castSpell($this->target, 'backlash');
    }

    public function testBacklashCanBeCastAtTheThreshold(): void
    {
        $this->target->resolve = 250;
        $this->target->save();

        $this->spellActionService->castSpell($this->target, 'backlash');

        $backlash = Spell::where('key', 'backlash')->firstOrFail();

        $this->assertEquals(
            1,
            DominionSpell::where('dominion_id', $this->target->id)->where('spell_id', $backlash->id)->count()
        );
    }

    public function testBacklashReflectsDamageInProportionToResolve(): void
    {
        $this->declareWar();
        $this->target->resolve = 250;
        $this->activateBacklash();

        $result = $this->spellActionService->castSpell($this->dominion, 'fireball', $this->target);

        // 5% of 40,000 peasants is 2,000, and a quarter of that rebounds
        $this->assertEquals(38000, $this->target->peasants, 'The target still takes the full hit');
        $this->assertEquals(39500, $this->dominion->peasants);
        $this->assertStringContainsString('rebounded', $result['message']);
    }

    public function testBacklashScalesToFullDamageAtMaximumResolve(): void
    {
        $this->declareWar();
        $this->target->resolve = 1000;
        $this->activateBacklash();

        $this->spellActionService->castSpell($this->dominion, 'fireball', $this->target);

        $this->assertEquals(38000, $this->dominion->peasants);
    }

    /**
     * Reflected losses are recorded against the caster like any other damage,
     * so their own realm can revive them.
     */
    public function testReflectedPeasantsAreRevivable(): void
    {
        $this->declareWar();
        $this->target->resolve = 1000;
        $this->activateBacklash();

        $this->spellActionService->castSpell($this->dominion, 'fireball', $this->target);

        $this->assertEquals(2000, $this->dominion->peasants_killed);
    }

    public function testBacklashSlowsFurtherResolveGain(): void
    {
        $this->declareWar();
        $this->target->resolve = 250;
        $this->activateBacklash();

        $this->spellActionService->castSpell($this->dominion, 'fireball', $this->target);

        $this->assertEquals(255, $this->target->resolve);
    }

    public function testResolveDecaysEachTickDuringAWar(): void
    {
        $this->declareWar();
        $this->target->protection_ticks_remaining = 0;
        $this->target->resolve = 250;
        $this->target->save();

        $this->app->make(TickService::class)->performTick($this->round);

        $this->seeInDatabase('dominions', [
            'id' => $this->target->id,
            'resolve' => 230,
        ]);
    }

    /**
     * Resolve answers a campaign, so it fades faster once the war is over.
     */
    public function testResolveDecaysFasterOutsideOfWar(): void
    {
        $this->target->protection_ticks_remaining = 0;
        $this->target->resolve = 250;
        $this->target->save();

        $this->app->make(TickService::class)->performTick($this->round);

        $this->seeInDatabase('dominions', [
            'id' => $this->target->id,
            'resolve' => 210,
        ]);
    }

    public function testResolveNeverFallsBelowZero(): void
    {
        $this->target->protection_ticks_remaining = 0;
        $this->target->resolve = 5;
        $this->target->save();

        $this->app->make(TickService::class)->performTick($this->round);

        $this->seeInDatabase('dominions', [
            'id' => $this->target->id,
            'resolve' => 0,
        ]);
    }
}
