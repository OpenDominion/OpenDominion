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
 * Friendly spells are open to every dominion, on realmmates and on themselves.
 * Realm roles will earn reduced cooldowns rather than exclusive access.
 */
class FriendlySpellAccessTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var SpellActionService */
    protected $spellActionService;

    /** @var Round */
    protected $round;

    /** @var Dominion A dominion holding no realm role */
    protected $dominion;

    /** @var Dominion */
    protected $realmmate;

    /** @var Dominion */
    protected $stranger;

    protected function setUp(): void
    {
        parent::setUp();

        $user = $this->createAndImpersonateUser();
        $this->round = $this->createRound('-4 days midnight');

        $this->dominion = $this->createDominionWithLegacyStats($user, $this->round, Race::where('name', 'Human')->firstOrFail());
        $this->dominion->land_plain = 8000;
        $this->dominion->resource_mana = 100000;

        $realmmateUser = $this->createUser();
        $this->realmmate = $this->createDominionWithLegacyStats(
            $realmmateUser,
            $this->round,
            Race::where('name', 'Human')->firstOrFail(),
            $this->dominion->realm
        );

        $strangerUser = $this->createUser();
        $this->stranger = $this->createDominionWithLegacyStats($strangerUser, $this->round, Race::where('name', 'Dark Elf')->firstOrFail());

        $this->spellActionService = $this->app->make(SpellActionService::class);

        global $mockRandomChance;
        $mockRandomChance = false;
    }

    protected function assertSpellActiveOn(Dominion $dominion, string $key): void
    {
        $this->assertEquals(
            1,
            DominionSpell::where('dominion_id', $dominion->id)
                ->where('spell_id', Spell::where('key', $key)->firstOrFail()->id)
                ->count(),
            "{$key} should be active on {$dominion->name}"
        );
    }

    public function testAnyDominionCanCastFriendlySpellsOnRealmmates(): void
    {
        $this->assertFalse($this->dominion->isMagister());
        $this->assertFalse($this->dominion->isMage());

        $this->spellActionService->castSpell($this->dominion, 'illumination', $this->realmmate);

        $this->assertSpellActiveOn($this->realmmate, 'illumination');
    }

    public function testFriendlySpellsCanBeCastOnYourself(): void
    {
        $result = $this->spellActionService->castSpell($this->dominion, 'illumination', $this->dominion);

        $this->assertSpellActiveOn($this->dominion, 'illumination');
        $this->assertStringContainsString('your dominion', $result['message']);
    }

    public function testInstantFriendlySpellsCanBeCastOnYourself(): void
    {
        $this->dominion->peasants = 30000;
        $this->dominion->peasants_killed = 4000;
        $this->dominion->save();

        $this->spellActionService->castSpell($this->dominion, 'revive_peasants', $this->dominion);

        $this->assertEquals(30100, $this->dominion->peasants);
        $this->assertEquals(3900, $this->dominion->peasants_killed);
    }

    public function testFriendlySpellsStillCannotLeaveTheRealm(): void
    {
        $this->expectException(GameException::class);
        $this->expectExceptionMessage('outside of your realm');

        $this->spellActionService->castSpell($this->dominion, 'illumination', $this->stranger);
    }

    public function testArcaneWardIsRetired(): void
    {
        $this->expectException(\LogicException::class);

        $this->spellActionService->castSpell($this->dominion, 'arcane_ward', $this->realmmate);
    }
}
