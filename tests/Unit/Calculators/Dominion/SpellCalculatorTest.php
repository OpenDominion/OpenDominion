<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Mockery as m;
use Mockery\Mock;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\Hero;
use OpenDominion\Models\Spell;
use OpenDominion\Tests\AbstractBrowserKitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SpellCalculator::class)]
class SpellCalculatorTest extends AbstractBrowserKitTestCase
{
    /** @var Mock|Dominion */
    protected $dominion;

    /** @var Mock|LandCalculator */
    protected $landCalculator;

    /** @var Mock|SpellHelper */
    protected $spellHelper;

    /** @var Mock|Spell */
    protected $spell;

    /** @var Mock|SpellCalculator */
    protected $sut;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dominion = m::mock(Dominion::class);
        $this->spell = m::mock(Spell::class);

        $this->sut = m::mock(SpellCalculator::class, [
            $this->landCalculator = m::mock(LandCalculator::class),
            $this->spellHelper = m::mock(SpellHelper::class),
        ])->makePartial();
    }

    public function testGetManaCost()
    {
        // Mock total land
        $this->landCalculator->shouldReceive('getTotalLand')->with($this->dominion)->andReturn(500);

        // Mock spell attributes
        $this->spell->shouldReceive('getAttribute')->with('cost_mana')->andReturn(5.0); // per acre
        $this->spell->shouldReceive('getAttribute')->with('key')->andReturn('test_spell');
        $this->spell->shouldReceive('getAttribute')->with('cooldown')->andReturn(false);

        // Mock dominion tech/wonder perks
        $this->dominion->shouldReceive('getTechPerkMultiplier')->with('spell_cost')->andReturn(0.1); // +10%
        $this->dominion->shouldReceive('getTechPerkMultiplier')->with('self_spell_cost')->andReturn(0.0);
        $this->dominion->shouldReceive('getTechPerkMultiplier')->with('racial_spell_cost')->andReturn(0.0);
        $this->dominion->shouldReceive('getTechPerkMultiplier')->with('fools_gold_cost')->andReturn(0.0);
        $this->dominion->shouldReceive('getWonderPerkMultiplier')->with('spell_cost')->andReturn(0.05); // +5%

        // Mock spell helper checks
        $this->spellHelper->shouldReceive('isSelfSpell')->with($this->spell)->andReturn(false);
        $this->spellHelper->shouldReceive('isRacialSelfSpell')->with($this->spell)->andReturn(false);
        $this->spellHelper->shouldReceive('isInfoOpSpell')->with($this->spell)->andReturn(false);

        // Mock dominion attributes
        $this->dominion->shouldReceive('getAttribute')->with('wizard_mastery')->andReturn(500);
        $this->dominion->shouldReceive('getAttribute')->with('hero')->andReturn(null);

        // Mock isSpellActive for Amplify Magic
        $this->sut->shouldReceive('isSpellActive')->with($this->dominion, 'amplify_magic')->andReturn(false);

        $result = $this->sut->getManaCost($this->dominion, $this->spell);

        // Expected calculation:
        // Base cost: 5.0 * 500 = 2500
        // Multiplier: 1 + 0.1 + 0.05 - (500/1000 * 20/100) = 1.15 - 0.1 = 1.05
        // Final: round(2500 * 1.05) = 2625
        $this->assertEquals(2625, $result);
    }

    public function testGetManaCostWithAmplifyMagic()
    {
        // Test with Amplify Magic affecting self spells
        $this->landCalculator->shouldReceive('getTotalLand')->with($this->dominion)->andReturn(400);

        $this->spell->shouldReceive('getAttribute')->with('cost_mana')->andReturn(3.0);
        $this->spell->shouldReceive('getAttribute')->with('key')->andReturn('self_spell');
        $this->spell->shouldReceive('getAttribute')->with('cooldown')->andReturn(false);

        $this->dominion->shouldReceive('getTechPerkMultiplier')->with('spell_cost')->andReturn(0.0);
        $this->dominion->shouldReceive('getTechPerkMultiplier')->with('self_spell_cost')->andReturn(0.1);
        $this->dominion->shouldReceive('getTechPerkMultiplier')->with('racial_spell_cost')->andReturn(0.0);
        $this->dominion->shouldReceive('getTechPerkMultiplier')->with('fools_gold_cost')->andReturn(0.0);
        $this->dominion->shouldReceive('getWonderPerkMultiplier')->with('spell_cost')->andReturn(0.0);

        $this->spellHelper->shouldReceive('isSelfSpell')->with($this->spell)->andReturn(true);
        $this->spellHelper->shouldReceive('isRacialSelfSpell')->with($this->spell)->andReturn(false);
        $this->spellHelper->shouldReceive('isInfoOpSpell')->with($this->spell)->andReturn(false);

        $this->dominion->shouldReceive('getAttribute')->with('wizard_mastery')->andReturn(800);
        $this->dominion->shouldReceive('getAttribute')->with('hero')->andReturn(null);

        // Amplify Magic is active
        $this->sut->shouldReceive('isSpellActive')->with($this->dominion, 'amplify_magic')->andReturn(true);
        $this->dominion->shouldReceive('getSpellPerkValue')->with('self_spell_cost')->andReturn(25); // +25%

        $result = $this->sut->getManaCost($this->dominion, $this->spell);

        // Expected calculation:
        // Base cost: 3.0 * 400 = 1200
        // Spell cost multiplier: 1 + 0.1 - (800/1000 * 20/100) = 1.1 - 0.16 = 0.94
        // Base with multiplier: round(1200 * 0.94) = 1128
        // Amplify Magic: round(1128 * (1 + 25/100)) = round(1128 * 1.25) = 1410
        $this->assertEquals(1410, $result);
    }

    public function testGetStrengthCost()
    {
        $this->spell->shouldReceive('getAttribute')->with('cost_strength')->andReturn(30.0);

        // Test without hero
        $this->dominion->shouldReceive('getAttribute')->with('hero')->andReturn(null);
        $this->spellHelper->shouldReceive('isSelfSpell')->with($this->spell)->andReturn(true);

        $result = $this->sut->getStrengthCost($this->dominion, $this->spell);
        $this->assertEquals(30.0, $result);
    }

    public function testGetStrengthCostWithHero()
    {
        $this->spell->shouldReceive('getAttribute')->with('cost_strength')->andReturn(25.0);

        // Test with hero affecting self spell cost
        $mockHero = m::mock(Hero::class);
        $mockHero->shouldReceive('getPerkValue')->with('self_spell_strength_cost')->andReturn(-5.0);
        $this->dominion->shouldReceive('getAttribute')->with('hero')->andReturn($mockHero);
        $this->spellHelper->shouldReceive('isSelfSpell')->with($this->spell)->andReturn(true);

        $result = $this->sut->getStrengthCost($this->dominion, $this->spell);
        $this->assertEquals(20.0, $result); // 25.0 + (-5.0) = 20.0
    }

    public function testCanCast()
    {
        $this->spell->shouldReceive('getAttribute')->with('cost_strength')->andReturn(25.0);

        // Mock sufficient resources
        $this->dominion->shouldReceive('getAttribute')->with('resource_mana')->andReturn(5000);
        $this->dominion->shouldReceive('getAttribute')->with('wizard_strength')->andReturn(50.0);

        $this->sut->shouldReceive('getManaCost')->with($this->dominion, $this->spell)->andReturn(3000);

        $result = $this->sut->canCast($this->dominion, $this->spell);
        $this->assertTrue($result);
    }

    public function testCannotCastInsufficientMana()
    {
        $this->spell->shouldReceive('getAttribute')->with('cost_strength')->andReturn(25.0);

        // Mock insufficient mana
        $this->dominion->shouldReceive('getAttribute')->with('resource_mana')->andReturn(1000);
        $this->dominion->shouldReceive('getAttribute')->with('wizard_strength')->andReturn(50.0);

        $this->sut->shouldReceive('getManaCost')->with($this->dominion, $this->spell)->andReturn(3000);

        $result = $this->sut->canCast($this->dominion, $this->spell);
        $this->assertFalse($result);
    }

    public function testCannotCastInsufficientStrength()
    {
        $this->spell->shouldReceive('getAttribute')->with('cost_strength')->andReturn(25.0);

        // Mock insufficient wizard strength
        $this->dominion->shouldReceive('getAttribute')->with('resource_mana')->andReturn(5000);
        $this->dominion->shouldReceive('getAttribute')->with('wizard_strength')->andReturn(20.0); // < 30

        $this->sut->shouldReceive('getManaCost')->with($this->dominion, $this->spell)->andReturn(3000);

        $result = $this->sut->canCast($this->dominion, $this->spell);
        $this->assertFalse($result);
    }

    public function testIsOnCooldown()
    {
        $this->sut->shouldReceive('getSpellCooldown')->with($this->dominion, $this->spell)->andReturn(5);

        $result = $this->sut->isOnCooldown($this->dominion, $this->spell);
        $this->assertTrue($result);
    }

    public function testIsNotOnCooldown()
    {
        $this->sut->shouldReceive('getSpellCooldown')->with($this->dominion, $this->spell)->andReturn(0);

        $result = $this->sut->isOnCooldown($this->dominion, $this->spell);
        $this->assertFalse($result);
    }

    public function testGetSpellCooldownNoHistory()
    {
        // Test spell with no cooldown
        $this->spell->shouldReceive('getAttribute')->with('cooldown')->andReturn(0);

        $result = $this->sut->getSpellCooldown($this->dominion, $this->spell);
        $this->assertEquals(0, $result);
    }

    public function testIsSpellActive()
    {
        // Mock dominion spells collection
        $spellsCollection = collect([
            (object)['key' => 'ares_call'],
            (object)['key' => 'forest_havens'],
            (object)['key' => 'gaias_blessing'],
        ]);

        $this->dominion->shouldReceive('getAttribute')->with('spells')
            ->andReturn($spellsCollection);

        $result = $this->sut->isSpellActive($this->dominion, 'ares_call');
        $this->assertTrue($result);

        $result = $this->sut->isSpellActive($this->dominion, 'nonexistent_spell');
        $this->assertFalse($result);
    }

    public function testGetSpellDuration()
    {
        $this->spell->shouldReceive('getAttribute')->with('duration')->andReturn(12);
        $this->spell->shouldReceive('getAttribute')->with('cooldown')->andReturn(false);

        // Test without Amplify Magic
        $this->sut->shouldReceive('isSpellActive')->with($this->dominion, 'amplify_magic')->andReturn(false);
        $this->spellHelper->shouldReceive('isSelfSpell')->with($this->spell)->andReturn(true);

        $result = $this->sut->getSpellDuration($this->dominion, $this->spell);
        $this->assertEquals(12, $result);
    }

    public function testGetSpellDurationWithAmplifyMagic()
    {
        $this->spell->shouldReceive('getAttribute')->with('duration')->andReturn(10);
        $this->spell->shouldReceive('getAttribute')->with('cooldown')->andReturn(false);

        // Test with Amplify Magic affecting self spell duration
        $this->sut->shouldReceive('isSpellActive')->with($this->dominion, 'amplify_magic')->andReturn(true);
        $this->spellHelper->shouldReceive('isSelfSpell')->with($this->spell)->andReturn(true);
        $this->dominion->shouldReceive('getSpellPerkValue')->with('self_spell_duration')->andReturn(50); // +50%

        $result = $this->sut->getSpellDuration($this->dominion, $this->spell);
        $this->assertEquals(15, $result); // round(10 * (1 + 50/100)) = round(10 * 1.5) = 15
    }

    public function testResolveSpellPerk()
    {
        // Mock different types of spell perk values
        $this->dominion->shouldReceive('getSpellPerkValue')->with('test_perk', ['self'])->andReturn(10.0);
        $this->dominion->shouldReceive('getSpellPerkValue')->with('test_perk', ['hostile'])->andReturn(0.0);
        $this->dominion->shouldReceive('getSpellPerkValue')->with('test_perk', ['war'])->andReturn(5.0);
        $this->dominion->shouldReceive('getSpellPerkValue')->with('test_perk', ['friendly'])->andReturn(3.0);
        $this->dominion->shouldReceive('getSpellPerkValue')->with('test_perk', ['effect'])->andReturn(2.0);

        $result = $this->sut->resolveSpellPerk($this->dominion, 'test_perk');
        $this->assertEquals(20.0, $result); // 10 + 0 + 5 + 3 + 2 = 20
    }

    public function testResolveSpellPerkWithProtection()
    {
        // Test hostile perk with damage protection
        $this->dominion->shouldReceive('getSpellPerkValue')->with('test_perk', ['self'])->andReturn(0.0);
        $this->dominion->shouldReceive('getSpellPerkValue')->with('test_perk', ['hostile'])->andReturn(-20.0); // hostile debuff
        $this->dominion->shouldReceive('getSpellPerkValue')->with('test_perk', ['war'])->andReturn(0.0);
        $this->dominion->shouldReceive('getSpellPerkValue')->with('test_perk', ['friendly'])->andReturn(0.0);
        $this->dominion->shouldReceive('getSpellPerkValue')->with('test_perk', ['effect'])->andReturn(0.0);

        // Protection spell reduces hostile effect
        $this->dominion->shouldReceive('getSpellPerkValue')
            ->with('test_perk_damage', ['self', 'friendly', 'effect'])->andReturn(30); // 30% reduction

        $result = $this->sut->resolveSpellPerk($this->dominion, 'test_perk');
        $this->assertEquals(-26.0, $result); // -20 * (1 + 30/100) = -20 * 1.3 = -26
    }
}
