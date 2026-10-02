<?php

namespace OpenDominion\Tests\Unit\Magic;

use Mockery as m;
use Mockery\Mock;
use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\PrestigeCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Wizard Guilds train wizards and produce more mana in place of the peasant
 * protection they used to provide.
 */
#[CoversClass(ProductionCalculator::class)]
class WizardGuildTest extends AbstractBrowserKitTestCase
{
    /** @var Mock|Dominion */
    protected $dominion;

    /** @var Mock|SpellCalculator */
    protected $spellCalculator;

    /** @var Mock|ProductionCalculator */
    protected $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dominion = m::mock(Dominion::class);
        $this->dominion->shouldReceive('getRoundPerkMultiplier', 'getRoundPerkValue')->andReturn(0.0)->byDefault();

        $this->sut = m::mock(ProductionCalculator::class, [
            m::mock(HeroCalculator::class),
            m::mock(ImprovementCalculator::class),
            m::mock(LandCalculator::class),
            m::mock(PopulationCalculator::class),
            m::mock(PrestigeCalculator::class),
            $this->spellCalculator = m::mock(SpellCalculator::class),
            m::mock(GuardMembershipService::class),
        ])->makePartial();
    }

    /**
     * Fractional wizards are not stored, so guilds only pay out per full 16.
     */
    public function testWizardProductionRoundsDownToWholeWizards(): void
    {
        $this->spellCalculator->shouldReceive('resolveSpellPerk')
            ->with($this->dominion, 'wizard_guilds_produce_military_unit3')->andReturn(0.0);

        $expectations = [
            [0, 0],
            [1, 0],
            [15, 0],
            [16, 1],
            [31, 1],
            [32, 2],
            [250, 15],
        ];

        foreach ($expectations as [$guilds, $expected]) {
            $this->dominion->shouldReceive('getAttribute')
                ->with('building_wizard_guild')->andReturn($guilds)->once();

            $this->assertSame(
                $expected,
                $this->sut->getWizardProduction($this->dominion),
                "{$guilds} Wizard Guilds should train {$expected} wizards per hour"
            );
        }
    }

    /**
     * Spellwright's Calling queues Adepts from the same guilds, so the guilds
     * must not also train wizards.
     */
    public function testRacialUnitProductionSupersedesWizardProduction(): void
    {
        $this->spellCalculator->shouldReceive('resolveSpellPerk')
            ->with($this->dominion, 'wizard_guilds_produce_military_unit3')->andReturn(0.05);

        $this->dominion->shouldReceive('getAttribute')
            ->with('building_wizard_guild')->andReturn(100);

        $this->assertSame(0, $this->sut->getWizardProduction($this->dominion));
    }

    public function testWizardGuildsProduceTenManaEach(): void
    {
        $this->spellCalculator->shouldReceive('resolveSpellPerk')
            ->with($this->dominion, 'wizard_guild_mana_production_raw')->andReturn(0.0);

        $this->dominion->shouldReceive('getAttribute')->with('building_tower')->andReturn(0);
        $this->dominion->shouldReceive('getAttribute')->with('building_wizard_guild')->andReturn(30);
        $this->dominion->shouldReceive('getTechPerkValue')->with('mana_production_raw')->andReturn(0);
        $this->dominion->shouldReceive('getTechPerkValue')->with('wartime_mana_production_raw')->andReturn(0);

        $this->assertEquals(300, $this->sut->getManaProductionRaw($this->dominion));
    }

    /**
     * Spellwright's Calling no longer carries a mana bonus, but the perk is
     * still honored if anything else grants it.
     */
    public function testWizardGuildManaPerkStillApplies(): void
    {
        $this->spellCalculator->shouldReceive('resolveSpellPerk')
            ->with($this->dominion, 'wizard_guild_mana_production_raw')->andReturn(5.0);

        $this->dominion->shouldReceive('getAttribute')->with('building_tower')->andReturn(0);
        $this->dominion->shouldReceive('getAttribute')->with('building_wizard_guild')->andReturn(30);
        $this->dominion->shouldReceive('getTechPerkValue')->with('mana_production_raw')->andReturn(0);
        $this->dominion->shouldReceive('getTechPerkValue')->with('wartime_mana_production_raw')->andReturn(0);

        $this->assertEquals(450, $this->sut->getManaProductionRaw($this->dominion));
    }
}
