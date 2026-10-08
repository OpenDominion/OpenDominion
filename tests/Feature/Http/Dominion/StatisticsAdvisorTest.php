<?php

namespace OpenDominion\Tests\Feature\Http\Dominion;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Tests\AbstractTestCase;

class StatisticsAdvisorTest extends AbstractTestCase
{
    use DatabaseTransactions;

    public function testSpellDamageSectionShowsWhatRealmSupportCanRestore(): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound();
        $dominion = $this->createDominion($user, $round);

        $dominion->peasants_killed = 1234;
        $dominion->improvement_damage_science = 1000;
        $dominion->improvement_damage_keep = 2000;
        $dominion->improvement_damage_forges = 3000;
        $dominion->improvement_damage_walls = 4000;
        $dominion->save();

        $this->selectDominion($dominion->fresh());

        $response = $this->get('/dominion/advisors/statistics');

        $response
            ->assertOk()
            ->assertSee('<th colspan="2">Spell Damage</th>', false)
            ->assertDontSee('Vulnerability')
            ->assertDontSee('Improvements Vulnerable')
            ->assertSeeInOrder([
                'Peasants Revivable:',
                '1,234',
                'Improvements Repairable:',
                '10,000',
                'Spell Damage Reduction:',
                'Lightning Damage Reduction:',
            ], false);
    }

    public function testSpellDamageSectionShowsZeroWithNothingOnRecord(): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound();
        $dominion = $this->createDominion($user, $round);

        $this->selectDominion($dominion->fresh());

        $response = $this->get('/dominion/advisors/statistics');

        $response
            ->assertOk()
            ->assertSeeInOrder([
                'Peasants Revivable:',
                '<strong>0</strong>',
                'Improvements Repairable:',
                '<strong>0</strong>',
            ], false);
    }
}
