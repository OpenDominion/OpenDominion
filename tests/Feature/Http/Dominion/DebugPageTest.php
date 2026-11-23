<?php

namespace OpenDominion\Tests\Feature\Http\Dominion;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Factories\RealmFactory;
use OpenDominion\Models\Spell;
use OpenDominion\Tests\AbstractTestCase;

class DebugPageTest extends AbstractTestCase
{
    use DatabaseTransactions;

    public function testDebugPanelShowsDominionAttributesWithoutSpellCasterData(): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('-4 days midnight');
        $dominion = $this->createAndSelectDominionWithLegacyStats($user, $round);
        $dominion->update(['military_unit1' => 2137, 'api_key' => 'private-debug-test-key']);
        $enemy = $this->createDominion(
            $this->createUser(),
            $round,
            realm: app(RealmFactory::class)->create($round, 'good'),
            attributes: ['name' => 'Rival Caster Fixture', 'military_unit1' => 81239]
        );
        $spell = Spell::where('key', 'plague')->firstOrFail();
        $dominion->spells()->syncWithoutDetaching([
            $spell->id => ['duration' => 12, 'cast_by_dominion_id' => $enemy->id],
        ]);

        $this->get('/dominion/debug')
            ->assertOk()
            ->assertSee('[resource_platinum]')
            ->assertSee('[military_unit1] => 2137')
            ->assertDontSee('private-debug-test-key')
            ->assertDontSee('81239')
            ->assertDontSee($enemy->name)
            ->assertDontSee('cast_by_dominion_id');
    }
}
