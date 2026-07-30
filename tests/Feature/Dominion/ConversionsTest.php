<?php

namespace OpenDominion\Tests\Feature\Dominion;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Models\Spell;
use OpenDominion\Services\Dominion\Actions\InvadeActionService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class ConversionsTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

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
        $this->round = $this->createRound('last week');

        $this->dominion = $this->createDominionWithLegacyStats($user, $this->round, Race::where('key', 'vampire')->firstOrFail());
        $this->dominion->protection_ticks_remaining = 0;
        $this->dominion->land_swamp = 2850;

        $targetUser = $this->createUser();
        $this->target = $this->createDominionWithLegacyStats($targetUser, $this->round, Race::where('key', 'human')->firstOrFail());
        $this->target->protection_ticks_remaining = 0;
        $this->target->land_plain = 2850;

        global $mockRandomChance;
        $mockRandomChance = false;
    }

    public function testFeastOfBloodNoLongerCancelsImmortalOrConvertsVampires()
    {
        $this->castSpell($this->dominion, 'feast_of_blood');

        $this->assertEquals(0, $this->dominion->getSpellPerkValue('cancels_immortal'));
        $this->assertEquals(0, $this->dominion->getSpellPerkValue('convert_vampires'));
    }

    public function testFeastOfBloodBoostsTheConversionRate()
    {
        $this->assertEquals(0.0, $this->dominion->getSpellPerkMultiplier('conversion_rate'));

        $this->castSpell($this->dominion, 'feast_of_blood');

        $this->assertEquals(0.25, $this->dominion->getSpellPerkMultiplier('conversion_rate'));
    }

    public function testSatiatedThirstReducesOffensivePowerByTenPercent()
    {
        $militaryCalculator = app(MilitaryCalculator::class);

        $before = $militaryCalculator->getOffensivePowerMultiplier($this->dominion);

        $this->castSpell($this->dominion, 'satiated_thirst');

        $after = $militaryCalculator->getOffensivePowerMultiplier($this->dominion);

        $this->assertEqualsWithDelta($before - 0.1, $after, 0.0001);
    }

    public function testSatiatedThirstStacksWithSelfOffenseSpells()
    {
        $militaryCalculator = app(MilitaryCalculator::class);

        $base = $militaryCalculator->getOffensivePowerMultiplier($this->dominion);

        // Crusade is +10% offense (self), Satiated Thirst is -10% (effect)
        $this->castSpell($this->dominion, 'crusade');
        $this->castSpell($this->dominion, 'satiated_thirst');

        $this->assertEqualsWithDelta($base, $militaryCalculator->getOffensivePowerMultiplier($this->dominion), 0.0001);
    }

    public function testSatiatedThirstIsAppliedByTheMilitaryCalculatorSim()
    {
        $militaryCalculator = app(MilitaryCalculator::class);

        $this->dominion->calc = [];
        $base = $militaryCalculator->getOffensivePowerMultiplier($this->dominion);

        $this->dominion->calc = ['satiated_thirst' => 1];

        $this->assertEqualsWithDelta($base - 0.1, $militaryCalculator->getOffensivePowerMultiplier($this->dominion), 0.0001);
    }

    public function testTheMilitaryCalculatorSimStacksSatiatedThirstOnTopOfASelfSpell()
    {
        $militaryCalculator = app(MilitaryCalculator::class);

        $this->dominion->calc = [];
        $base = $militaryCalculator->getOffensivePowerMultiplier($this->dominion);

        // The sim's self-spell pool is not race filtered; crusade is +10% offense
        $this->dominion->calc = ['crusade' => 1];
        $this->assertEqualsWithDelta($base + 0.1, $militaryCalculator->getOffensivePowerMultiplier($this->dominion), 0.0001);

        $this->dominion->calc = ['crusade' => 1, 'satiated_thirst' => 1];
        $this->assertEqualsWithDelta($base, $militaryCalculator->getOffensivePowerMultiplier($this->dominion), 0.0001);
    }

    public function testInvadingWithFeastOfBloodAppliesSatiatedThirst()
    {
        $this->castSpell($this->dominion, 'feast_of_blood');

        $this->assertNull($this->activeSpell($this->dominion, 'satiated_thirst'));

        $this->invade();

        $satiatedThirst = $this->activeSpell($this->dominion, 'satiated_thirst');

        $this->assertNotNull($satiatedThirst, 'Satiated Thirst should be applied after attacking');
        $this->assertEquals(48, $satiatedThirst->pivot->duration);
    }

    public function testInvadingWithoutFeastOfBloodDoesNotApplySatiatedThirst()
    {
        $this->invade();

        $this->assertNull($this->activeSpell($this->dominion, 'satiated_thirst'));
    }

    protected function invade(): void
    {
        $this->dominion->military_unit3 = 10000; // Kindred, kept home to satisfy the 40% rule
        $this->dominion->military_unit4 = 15000; // Bloodreavers
        $this->target->military_draftees = 0;
        $this->target->military_unit2 = 15000;

        app(InvadeActionService::class)->invade($this->dominion, $this->target, [4 => 14000], false);

        $this->dominion->refresh();
    }

    protected function castSpell(Dominion $dominion, string $spellKey): void
    {
        DominionSpell::create([
            'dominion_id' => $dominion->id,
            'spell_id' => Spell::where('key', $spellKey)->firstOrFail()->id,
            'duration' => 12,
        ]);

        $dominion->load('spells');
    }

    protected function activeSpell(Dominion $dominion, string $spellKey): ?Spell
    {
        $dominion->load('spells');

        return $dominion->spells->firstWhere('key', $spellKey);
    }
}
