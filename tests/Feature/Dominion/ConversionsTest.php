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

    public function testBottomFeedingWithFeastOfBloodDoesNotApplySatiatedThirst()
    {
        $this->castSpell($this->dominion, 'feast_of_blood');

        $result = $this->invade(0.6);

        $this->assertTrue($result['result']['success'], 'Fixture assumption: the invasion lands');
        $this->assertLessThan(75, $result['result']['range'], 'Fixture assumption: this is a bottom feed');

        $this->assertNull(
            $this->activeSpell($this->dominion, 'satiated_thirst'),
            'Satiated Thirst should not apply when the conversion bonus does not'
        );
    }

    public function testFeastOfBloodConversionBonusAppliesInRangeButNotWhenBottomFeeding()
    {
        $inRangeWithout = $this->convertsForFreshPair(1.0, false);
        $inRangeWith = $this->convertsForFreshPair(1.0, true);

        $this->assertGreaterThan(
            $inRangeWithout,
            $inRangeWith,
            'In range, Feast of Blood should increase conversions'
        );

        $bottomFeedWithout = $this->convertsForFreshPair(0.6, false);
        $bottomFeedWith = $this->convertsForFreshPair(0.6, true);

        $this->assertGreaterThan(0, $bottomFeedWithout, 'Fixture assumption: bottom feeding still converts');
        $this->assertEquals(
            $bottomFeedWithout,
            $bottomFeedWith,
            'Below 75% the conversion rate bonus should have no effect'
        );
    }

    /**
     * Runs an invasion with a brand new attacker/target pair and returns the
     * total units converted, so two scenarios can be compared in one test.
     */
    protected function convertsForFreshPair(float $targetSizeRatio, bool $withFeastOfBlood): int
    {
        $attacker = $this->createDominionWithLegacyStats($this->createUser(), $this->round, Race::where('key', 'vampire')->firstOrFail());
        $attacker->protection_ticks_remaining = 0;
        $attacker->land_swamp = 2850;

        $target = $this->createDominionWithLegacyStats($this->createUser(), $this->round, Race::where('key', 'human')->firstOrFail());
        $target->protection_ticks_remaining = 0;

        if ($withFeastOfBlood) {
            $this->castSpell($attacker, 'feast_of_blood');
            $this->assertEquals(0.25, $attacker->getSpellPerkMultiplier('conversion_rate'), 'Fixture assumption: Feast of Blood is active');
        }

        $result = $this->invadeWith($attacker, $target, $targetSizeRatio);

        $this->assertTrue($result['result']['success'], 'Fixture assumption: the invasion lands');

        return array_sum($result['attacker']['conversion'] ?? []);
    }

    protected function invade(float $targetSizeRatio = 1.0): array
    {
        return $this->invadeWith($this->dominion, $this->target, $targetSizeRatio);
    }

    protected function invadeWith(Dominion $attacker, Dominion $target, float $targetSizeRatio): array
    {
        $attacker->military_unit3 = 10000; // Kindred, kept home to satisfy the 40% rule
        $attacker->military_unit4 = 15000; // Bloodreavers

        // Size the target purely in plains so total land is exact
        $attackerLand = $this->totalLand($attacker);
        foreach (['plain', 'mountain', 'swamp', 'cavern', 'forest', 'hill', 'water'] as $type) {
            $target->{'land_' . $type} = 0;
        }
        $target->land_plain = (int) round($attackerLand * $targetSizeRatio);

        $target->military_draftees = 0;
        $target->military_unit2 = (int) round(15000 * $targetSizeRatio);

        // InvadeActionService is a singleton and $invasionResult is instance state
        // written only once per key, so each invasion needs a fresh instance or
        // the second one reports the first one's results. build() skips the
        // singleton cache while still resolving constructor dependencies.
        $invadeActionService = $this->app->build(InvadeActionService::class);
        $invadeActionService->invade($attacker, $target, [4 => 14000], false);

        $attacker->refresh();

        $resultProperty = new \ReflectionProperty($invadeActionService, 'invasionResult');
        $resultProperty->setAccessible(true);

        return $resultProperty->getValue($invadeActionService);
    }

    protected function totalLand(Dominion $dominion): int
    {
        return $dominion->land_plain
            + $dominion->land_mountain
            + $dominion->land_swamp
            + $dominion->land_cavern
            + $dominion->land_forest
            + $dominion->land_hill
            + $dominion->land_water;
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
