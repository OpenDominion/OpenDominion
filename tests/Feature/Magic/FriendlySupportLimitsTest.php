<?php

namespace OpenDominion\Tests\Feature\Magic;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Models\Spell;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

/**
 * Damage is range limited, so undoing it is too. A realm cannot mend its
 * largest dominion with dominions nobody could have attacked, and a smaller
 * realmmate mends proportionally less than a peer.
 */
class FriendlySupportLimitsTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var SpellActionService */
    protected $spellActionService;

    /** @var Round */
    protected $round;

    /** @var Dominion The dominion being supported */
    protected $target;

    protected function setUp(): void
    {
        parent::setUp();

        $user = $this->createAndImpersonateUser();
        $this->round = $this->createRound('-4 days midnight');

        $this->target = $this->createDominionWithLegacyStats($user, $this->round, Race::where('name', 'Human')->firstOrFail());
        $this->target->land_plain = 8000;
        $this->target->peasants = 30000;
        $this->target->peasants_killed = 4000;
        $this->target->resource_mana = 100000;
        $this->target->save();

        $this->spellActionService = $this->app->make(SpellActionService::class);

        global $mockRandomChance;
        $mockRandomChance = false;
    }

    protected function createRealmmate(int $land): Dominion
    {
        $dominion = $this->createDominionWithLegacyStats(
            // A unique email, since this test creates several users in one run
            $this->createUser(null, ['email' => uniqid('support', true) . '@example.com']),
            $this->round,
            Race::where('name', 'Human')->firstOrFail(),
            $this->target->realm
        );

        $dominion->land_plain = $land;
        $dominion->resource_mana = 100000;
        $dominion->save();

        return $dominion;
    }

    public function testAPeerRestoresTheFullAmount(): void
    {
        $peer = $this->createRealmmate(8000);

        $this->spellActionService->castSpell($peer, 'revive_peasants', $this->target);

        // 2.5% of 4,000 peasants on record
        $this->assertEquals(3900, $this->target->peasants_killed);
    }

    public function testASmallerRealmmateRestoresProportionallyLess(): void
    {
        $smaller = $this->createRealmmate(4000);

        $this->spellActionService->castSpell($smaller, 'revive_peasants', $this->target);

        // Half the size, so roughly half of a peer's 100 peasants. Total land
        // includes the starting land of the other types, so it is not exact.
        $revived = 4000 - $this->target->peasants_killed;

        $this->assertGreaterThan(45, $revived);
        $this->assertLessThan(55, $revived);
    }

    public function testABiggerRealmmateIsNoMoreEffective(): void
    {
        $bigger = $this->createRealmmate(16000);

        $this->spellActionService->castSpell($bigger, 'revive_peasants', $this->target);

        $this->assertEquals(3900, $this->target->peasants_killed);
    }

    public function testARealmmateOutsideRangeCannotHelp(): void
    {
        $tiny = $this->createRealmmate(2000);

        $this->expectException(GameException::class);
        $this->expectExceptionMessage('too far outside your range');

        $this->spellActionService->castSpell($tiny, 'revive_peasants', $this->target);
    }

    public function testSupportingYourselfIsUnaffected(): void
    {
        $this->spellActionService->castSpell($this->target, 'revive_peasants', $this->target);

        $this->assertEquals(3900, $this->target->peasants_killed);
    }

    /**
     * Only the spells that undo damage are limited. Wards and the like remain
     * available to the whole realm.
     */
    public function testOtherFriendlySpellsIgnoreRangeAndSize(): void
    {
        $tiny = $this->createRealmmate(2000);

        $this->spellActionService->castSpell($tiny, 'illumination', $this->target);

        $this->assertEquals(
            1,
            DominionSpell::where('dominion_id', $this->target->id)
                ->where('spell_id', Spell::where('key', 'illumination')->firstOrFail()->id)
                ->count()
        );
    }
}
