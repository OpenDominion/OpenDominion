<?php

namespace OpenDominion\Tests\Feature\Dominion;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\Race;
use OpenDominion\Models\Spell;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class InfernalCommandTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var Dominion */
    protected $dominion;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    protected function setUp(): void
    {
        parent::setUp();

        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('-7 days');

        $this->dominion = $this->createDominionWithLegacyStats($user, $round, Race::where('key', 'demon')->firstOrFail());
        $this->militaryCalculator = app(MilitaryCalculator::class);
    }

    public function testInfernalImpBaseOffenseIsThreeAndAHalf()
    {
        $imp = $this->dominion->race->units->firstWhere('slot', 1);

        $this->assertEquals(3.5, $imp->power_offense);
    }

    public function testInfernalCommandReducesInfernalImpOffense()
    {
        $imp = $this->dominion->race->units->firstWhere('slot', 1);

        $this->assertEquals(3.5, $this->militaryCalculator->getUnitPowerWithPerks($this->dominion, null, null, $imp, 'offense'));

        DominionSpell::create([
            'dominion_id' => $this->dominion->id,
            'spell_id' => Spell::where('key', 'infernal_command')->firstOrFail()->id,
            'duration' => 12,
        ]);
        $this->dominion->load('spells');

        $this->assertEquals(3.0, $this->militaryCalculator->getUnitPowerWithPerks($this->dominion, null, null, $imp, 'offense'));
    }

    public function testInfernalCommandNoLongerAppliesCorruption()
    {
        DominionSpell::create([
            'dominion_id' => $this->dominion->id,
            'spell_id' => Spell::where('key', 'infernal_command')->firstOrFail()->id,
            'duration' => 12,
        ]);
        $this->dominion->load('spells');

        $this->assertEquals(0, $this->dominion->getSpellPerkValue('apply_corruption'));
    }

    public function testTheSimAgreesWithTheLivePathOnInfernalImpOffense()
    {
        $imp = $this->dominion->race->units->firstWhere('slot', 1);

        $this->dominion->calc = [];
        $this->assertEquals(3.5, $this->militaryCalculator->getUnitPowerWithPerks($this->dominion, null, null, $imp, 'offense'));

        $this->dominion->calc = ['infernal_command' => 1];
        $this->assertEquals(3.0, $this->militaryCalculator->getUnitPowerWithPerks($this->dominion, null, null, $imp, 'offense'));
    }
}
