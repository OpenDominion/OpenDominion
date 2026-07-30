<?php

namespace OpenDominion\Tests\Feature\Http\Dominion;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\Race;
use OpenDominion\Models\Spell;
use OpenDominion\Tests\AbstractTestCase;

class CalculationsMilitaryTest extends AbstractTestCase
{
    use DatabaseTransactions;

    public function testOffensivePenaltyEffectSpellDefaultsToOff(): void
    {
        $dominion = $this->createVampireDominion();

        $input = $this->offenseEffectSpellInput($dominion);

        $this->assertNotNull($input, 'Satiated Thirst should render as an offense calc field');
        $this->assertStringNotContainsString('checked', $input);
    }

    public function testOffensivePenaltyEffectSpellStaysOffEvenWhenActive(): void
    {
        $dominion = $this->createVampireDominion();

        DominionSpell::create([
            'dominion_id' => $dominion->id,
            'spell_id' => Spell::where('key', 'satiated_thirst')->value('id'),
            'duration' => 48,
            'cast_by_dominion_id' => $dominion->id,
        ]);

        $input = $this->offenseEffectSpellInput($dominion);

        $this->assertNotNull($input);
        $this->assertStringNotContainsString('checked', $input);
    }

    public function testRacialOffensiveBonusSpellDefaultsToOn(): void
    {
        $dominion = $this->createVampireDominion(Race::where('key', 'orc')->firstOrFail());

        $response = $this->get(route('dominion.calculations.military', ['dominion' => $dominion->id]));
        $response->assertOk();

        // Bloodrage is +10% offense
        $this->assertMatchesRegularExpression(
            '/<input[^>]*name="calc\[bloodrage\]"[^>]*\bchecked\b[^>]*>/',
            $response->getContent()
        );
    }

    public function testOffenseEffectSpellIsNotRenderedForOtherRaces(): void
    {
        $dominion = $this->createVampireDominion(Race::where('key', 'human')->firstOrFail());

        $this->assertNull($this->offenseEffectSpellInput($dominion));
    }

    protected function createVampireDominion(?Race $race = null): Dominion
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('-7 days');
        $race = $race ?: Race::where('key', 'vampire')->firstOrFail();
        $dominion = $this->createDominion($user, $round, $race);

        $this->selectDominion($dominion->fresh());

        return $dominion;
    }

    /**
     * Returns the rendered `calc[satiated_thirst]` input tag, or null when absent.
     */
    protected function offenseEffectSpellInput(Dominion $dominion): ?string
    {
        $response = $this->get(route('dominion.calculations.military', ['dominion' => $dominion->id]));
        $response->assertOk();

        $matched = preg_match(
            '/<input[^>]*name="calc\[satiated_thirst\]"[^>]*>/',
            $response->getContent(),
            $matches
        );

        return $matched ? $matches[0] : null;
    }
}
