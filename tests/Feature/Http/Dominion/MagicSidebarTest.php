<?php

namespace OpenDominion\Tests\Feature\Http\Dominion;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\Spell;
use OpenDominion\Tests\AbstractTestCase;

class MagicSidebarTest extends AbstractTestCase
{
    use DatabaseTransactions;

    public function testExpiringBeneficialSpellsUseASeparateWarningBadge(): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound();
        $dominion = $this->createDominion($user, $round);

        foreach (['ares_call' => 1, 'midas_touch' => 12] as $spellKey => $duration) {
            DominionSpell::create([
                'dominion_id' => $dominion->id,
                'spell_id' => Spell::where('key', $spellKey)->value('id'),
                'duration' => $duration,
                'cast_by_dominion_id' => $dominion->id,
            ]);
        }

        $this->selectDominion($dominion->fresh());

        $response = $this->get('/dominion/status');

        $response
            ->assertOk()
            ->assertSee('<span class="badge text-bg-warning" title="Beneficial spells expiring next tick">1</span>', false)
            ->assertSee('<span class="badge text-bg-info">1</span>', false);
    }
}
