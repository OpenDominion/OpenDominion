<?php

namespace OpenDominion\Tests\Unit\Services\Action;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\Hero;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Models\Spell;
use OpenDominion\Services\Dominion\Actions\EspionageActionService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class EspionageActionServiceTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var EspionageActionService */
    protected $espionageActionService;

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
        $this->round = $this->createRound('-4 days midnight');

        $this->dominion = $this->createDominionWithLegacyStats($user, $this->round, Race::where('name', 'Halfling')->firstOrFail());
        $this->dominion->protection_ticks_remaining = 0;
        $this->dominion->land_plain = 10000;

        $targetUser = $this->createUser();
        $this->target = $this->createDominionWithLegacyStats($targetUser, $this->round, Race::where('name', 'Nomad')->firstOrFail());
        $this->target->protection_ticks_remaining = 0;
        $this->target->land_plain = 10000;

        $this->espionageActionService = $this->app->make(EspionageActionService::class);

        global $mockRandomChance;
        $mockRandomChance = false;
    }

    public function testPerformOperation_SameSpa_LoseQuarterPercent()
    {
        // Arrange
        $this->dominion->military_spies = 5000;
        $this->target->military_spies = 5000;

        // Act
        $this->espionageActionService->performOperation($this->dominion, 'barracks_spy', $this->target);

        // Assert
        $this->assertEquals(4988, $this->dominion->military_spies);
    }

    public function testPerformOperation_MuchLowerSpa_LoseMaxOnePercent()
    {
        // Arrange
        $this->dominion->military_spies = 5000;
        $this->target->military_spies = 50000;

        // Act
        $this->espionageActionService->performOperation($this->dominion, 'barracks_spy', $this->target);

        // Assert
        $this->assertEquals(4950, $this->dominion->military_spies);
    }

    public function testPerformOperation_MuchHigherSpa_LoseQuarterPercent()
    {
        // Arrange
        $this->dominion->military_spies = 5000;
        $this->target->military_spies = 500;

        // Act
        $this->espionageActionService->performOperation($this->dominion, 'barracks_spy', $this->target);

        // Assert
        $this->assertEquals(4988, $this->dominion->military_spies);
    }

    public function testPerformOperation_SameSpa_LoseMilitary()
    {
        // Arrange
        $this->dominion->military_unit3 = 20000;
        $this->target->military_spies = 3000;

        // Act
        $this->espionageActionService->performOperation($this->dominion, 'barracks_spy', $this->target);

        // Assert
        $this->assertEquals(19997, $this->dominion->military_unit3);
    }

    public function testStealPlatinum_TargetFullyProtectedByProduction_StealsZero()
    {
        global $mockRandomChance;
        $mockRandomChance = true;

        // Arrange: target's raw platinum production exceeds its stockpile, so the
        // unprotected portion is zero and the target_amount ceiling collapses to 0.
        $this->dominion->military_spies = 100000;
        $this->target->military_spies = 0;
        $this->target->resource_platinum = 1000;       // tiny stockpile
        $this->target->building_alchemy = 1000;        // 1000 * 45 = 45000 raw production

        $platinumBefore = $this->dominion->resource_platinum;

        // Act
        $result = $this->espionageActionService->performOperation($this->dominion, 'steal_platinum', $this->target);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals($platinumBefore, $this->dominion->resource_platinum);
        $this->assertEquals(1000, $this->target->resource_platinum);
    }

    public function testStealMana_AttackerWithZeroProduction_UsesPerAcreFloor()
    {
        global $mockRandomChance;
        $mockRandomChance = true;

        // Arrange: attacker has no towers/wizard guilds, target sits on a large
        // unprotected mana pile. The per-acre floor (2/acre) should be the
        // binding self_production cap, not zero.
        $this->dominion->military_spies = 100000;
        $this->dominion->building_tower = 0;
        $this->dominion->building_wizard_guild = 0;
        $this->target->military_spies = 0;
        $this->target->building_tower = 0;             // no protection
        $this->target->building_wizard_guild = 0;
        $this->target->resource_mana = 100000000;      // huge so target_amount isn't binding

        // Act
        $result = $this->espionageActionService->performOperation($this->dominion, 'steal_mana', $this->target);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $this->dominion->resource_mana);
    }

    public function testStealPlatinum_SmallerTarget_AppliesSizePenalty()
    {
        global $mockRandomChance;
        $mockRandomChance = true;

        // Arrange: shrink target so range ≈ 75%. With curve max(r^4.5, 0.01),
        // 0.75^4.5 ≈ 0.274, so the smaller-target take should be roughly 27%
        // of the same-size take (allowing some rounding slack).
        $this->dominion->military_spies = 100000;
        $this->target->military_spies = 0;
        $this->target->resource_platinum = 100000000;  // huge so target_amount isn't binding
        $this->target->building_alchemy = 0;            // no protection
        $this->target->peasants = 0;                    // no peasant protection

        // Same-size baseline
        $sameSizePlatinumBefore = $this->dominion->resource_platinum;
        $this->espionageActionService->performOperation($this->dominion, 'steal_platinum', $this->target);
        $sameSizeStolen = $this->dominion->resource_platinum - $sameSizePlatinumBefore;
        $this->assertGreaterThan(0, $sameSizeStolen);

        // Reset attacker spy strength so we can perform another op
        $this->dominion->spy_strength = 100;

        // Shrink target to ~75% range
        $shrinkBy = $this->totalLand($this->target) - (int) round($this->totalLand($this->dominion) * 0.75);
        $this->target->land_plain -= $shrinkBy;

        $smallerPlatinumBefore = $this->dominion->resource_platinum;
        $this->espionageActionService->performOperation($this->dominion, 'steal_platinum', $this->target);
        $smallerStolen = $this->dominion->resource_platinum - $smallerPlatinumBefore;

        // Expect ~27% (0.75^4.5), allow a 5pp window.
        $ratio = $smallerStolen / $sameSizeStolen;
        $this->assertGreaterThan(0.22, $ratio);
        $this->assertLessThan(0.32, $ratio);
    }

    public function testStealPlatinum_TargetOutOfRange_StillThrows()
    {
        // Arrange: shrink target far below the non-guard range floor (40%).
        $this->dominion->military_spies = 100000;
        $shrinkBy = $this->totalLand($this->target) - (int) round($this->totalLand($this->dominion) * 0.30);
        $this->target->land_plain -= $shrinkBy;

        // Assert: isInRange still blocks below MINIMUM_RANGE = 0.4.
        $this->expectException(GameException::class);
        $this->espionageActionService->performOperation($this->dominion, 'steal_platinum', $this->target);
    }

    public function testStealPlatinum_BotTarget_Throws()
    {
        $this->dominion->military_spies = 100000;
        $this->target->user_id = null;
        $this->target->save();

        $this->expectException(GameException::class);
        $this->expectExceptionMessage('cannot perform resource theft on bots');
        $this->espionageActionService->performOperation($this->dominion, 'steal_platinum', $this->target);
    }

    public function testStealPlatinum_DuringEarlyRound_Throws()
    {
        // Move the round start inside the 3-day theft window.
        $this->round->start_date = Carbon::parse('-1 day midnight');
        $this->round->save();
        $this->dominion->military_spies = 100000;

        $this->expectException(GameException::class);
        $this->expectExceptionMessage('first three days of the round');
        $this->espionageActionService->performOperation($this->dominion, 'steal_platinum', $this->target);
    }

    public function testStealPlatinum_AttackerWithZeroSpies_Throws()
    {
        $this->dominion->military_spies = 0;

        $this->expectException(GameException::class);
        $this->expectExceptionMessage('spy force is too weak');
        $this->espionageActionService->performOperation($this->dominion, 'steal_platinum', $this->target);
    }

    public function testStealPlatinum_LargerTarget_NoBonusFromSizeClamp()
    {
        global $mockRandomChance;
        $mockRandomChance = true;

        // Same baseline setup as the size-penalty test.
        $this->dominion->military_spies = 100000;
        $this->target->military_spies = 0;
        $this->target->resource_platinum = 100000000;
        $this->target->building_alchemy = 0;
        $this->target->peasants = 0;

        // Same-size baseline.
        $sameSizeBefore = $this->dominion->resource_platinum;
        $this->espionageActionService->performOperation($this->dominion, 'steal_platinum', $this->target);
        $sameSizeStolen = $this->dominion->resource_platinum - $sameSizeBefore;

        // Reset spy strength and grow the target to ~150% of attacker.
        $this->dominion->spy_strength = 100;
        $this->target->land_plain += (int) round($this->totalLand($this->dominion) * 0.5);

        $largerBefore = $this->dominion->resource_platinum;
        $this->espionageActionService->performOperation($this->dominion, 'steal_platinum', $this->target);
        $largerStolen = $this->dominion->resource_platinum - $largerBefore;

        // sizePenalty clamps to 1.0 for relativeSize >= 1, so the larger-target
        // take should match the same-size take (allow 1% rounding slack).
        $this->assertGreaterThan(0, $sameSizeStolen);
        $this->assertEqualsWithDelta(1.0, $largerStolen / $sameSizeStolen, 0.01);
    }

    public function testStealPlatinum_FoolsGold_StealsZero()
    {
        global $mockRandomChance;
        $mockRandomChance = true;

        // Activate Fool's Gold on the target. Don't refresh() the target —
        // that would discard the in-memory land_plain override from setUp and
        // put the dominion out of range.
        DominionSpell::create([
            'dominion_id' => $this->target->id,
            'spell_id'    => Spell::where('key', 'fools_gold')->firstOrFail()->id,
            'duration'    => 12,
        ]);

        $this->dominion->military_spies = 100000;
        $this->target->resource_platinum = 100000000;
        $this->target->building_alchemy = 0;
        $platinumBefore = $this->dominion->resource_platinum;

        $result = $this->espionageActionService->performOperation($this->dominion, 'steal_platinum', $this->target);

        $this->assertTrue($result['success']);
        $this->assertEquals($platinumBefore, $this->dominion->resource_platinum);
    }

    public function testStealPlatinum_FailedOperation_NoTransferAndSpiesLost()
    {
        // mockRandomChance defaults to false in setUp -> operation fails.
        $this->dominion->military_spies = 5000;
        $this->target->military_spies = 50000; // huge SPA disadvantage forces failure
        $this->target->resource_platinum = 100000000;
        $platinumBefore = $this->dominion->resource_platinum;
        $spiesBefore = $this->dominion->military_spies;

        $result = $this->espionageActionService->performOperation($this->dominion, 'steal_platinum', $this->target);

        $this->assertFalse($result['success']);
        $this->assertEquals($platinumBefore, $this->dominion->resource_platinum);
        $this->assertLessThan($spiesBefore, $this->dominion->military_spies);
    }

    public function testStealLumber_PartialProtection_SubtractsRawProductionFromCeiling()
    {
        global $mockRandomChance;
        $mockRandomChance = true;

        // Make sure target_amount is the binding constraint by giving the
        // attacker plenty of lumber production (so maxDominion is huge) and
        // setting the target stockpile high enough that 5% << everything else.
        $this->dominion->military_spies = 100000;
        $this->dominion->building_lumberyard = 20000; // huge maxDominion (>> maxTarget)
        $this->target->military_spies = 0;
        $this->target->building_lumberyard = 0;
        $this->target->building_forest_haven = 0;
        $this->target->resource_lumber = 10000000;

        // Baseline: zero protection -> maxTarget = 10,000,000 * 5% = 500,000
        $unprotectedBefore = $this->dominion->resource_lumber;
        $this->espionageActionService->performOperation($this->dominion, 'steal_lumber', $this->target);
        $unprotectedStolen = $this->dominion->resource_lumber - $unprotectedBefore;

        // Protected: 100 target lumberyards -> 5000 raw production
        // maxTarget = (10,000,000 - 5,000) * 5% = 499,750
        // Difference = 250 lumber (× same multipliers/size penalty).
        $this->dominion->spy_strength = 100;
        $this->target->resource_lumber = 10000000;
        $this->target->building_lumberyard = 100;

        $protectedBefore = $this->dominion->resource_lumber;
        $this->espionageActionService->performOperation($this->dominion, 'steal_lumber', $this->target);
        $protectedStolen = $this->dominion->resource_lumber - $protectedBefore;

        // Protection reduces theft by 50/lumberyard × 5% = 2.5 lumber per
        // building. Allow tiny rounding slack on the 250-lumber gap.
        $diff = $unprotectedStolen - $protectedStolen;
        $this->assertGreaterThan(240, $diff);
        $this->assertLessThan(260, $diff);
    }

    public function testStealOre_AttackerProductionExceedsFloor_UsesProductionCap()
    {
        global $mockRandomChance;
        $mockRandomChance = true;

        // Keep attacker at default ~10,150 acres (floor = ~30,450 ore).
        // Compare zero-mine attacker (floor binds) vs heavy-mine attacker
        // (production cap binds) — production should let them steal more.
        $this->dominion->military_spies = 100000;
        $this->target->military_spies = 0;
        $this->target->resource_ore = 100000000;
        $this->target->building_ore_mine = 0; // no target protection

        // Zero ore-mine baseline.
        $this->dominion->building_ore_mine = 0;
        $beforeFloor = $this->dominion->resource_ore;
        $this->espionageActionService->performOperation($this->dominion, 'steal_ore', $this->target);
        $stolenWithFloor = $this->dominion->resource_ore - $beforeFloor;

        // Heavy ore-mine build: 1500 mines × 60 = 90,000 raw, × 0.68 = 61,200
        // production cap, well above the ~30,450 floor.
        $this->dominion->spy_strength = 100;
        $this->dominion->building_ore_mine = 1500;
        $beforeProd = $this->dominion->resource_ore;
        $this->espionageActionService->performOperation($this->dominion, 'steal_ore', $this->target);
        $stolenWithProd = $this->dominion->resource_ore - $beforeProd;

        $this->assertGreaterThan($stolenWithFloor, $stolenWithProd);
    }

    public function testPerformOperation_DailyXp_UnderCap_AwardsFullRawXp()
    {
        global $mockRandomChance;
        $mockRandomChance = true;

        $this->dominion->military_spies = 5000;
        $this->target->military_spies = 5000;

        $hero = Hero::create([
            'dominion_id' => $this->dominion->id,
            'name' => 'Test Hero',
            'class' => 'alchemist',
            'experience' => 0,
            'class_data' => [],
        ]);

        $this->dominion->daily_xp = 0;
        $heroXpBefore = (float) $hero->experience;

        $result = $this->espionageActionService->performOperation($this->dominion, 'barracks_spy', $this->target);

        $this->assertTrue($result['success']);
        $hero->refresh();

        // Halfling default info op: xpValue=2, spy coefficient=0.5, raw=1.0
        $this->assertEqualsWithDelta(1.0, (float) $this->dominion->daily_xp, 0.0001);
        $this->assertGreaterThan($heroXpBefore, (float) $hero->experience);
    }

    public function testPerformOperation_DailyXp_CrossesCap_ClampsToCap()
    {
        global $mockRandomChance;
        $mockRandomChance = true;

        $this->dominion->military_spies = 5000;
        $this->target->military_spies = 5000;

        Hero::create([
            'dominion_id' => $this->dominion->id,
            'name' => 'Test Hero',
            'class' => 'alchemist',
            'experience' => 0,
            'class_data' => [],
        ]);

        // raw per op is 1.0; sit 0.5 below the cap so this op crosses it.
        $this->dominion->daily_xp = HeroCalculator::DAILY_OPS_XP_CAP - 0.5;

        $result = $this->espionageActionService->performOperation($this->dominion, 'barracks_spy', $this->target);

        $this->assertTrue($result['success']);
        $this->assertEqualsWithDelta(HeroCalculator::DAILY_OPS_XP_CAP, (float) $this->dominion->daily_xp, 0.0001);
    }

    public function testPerformOperation_DailyXp_AtCap_AwardsZeroXpButOpSucceeds()
    {
        global $mockRandomChance;
        $mockRandomChance = true;

        $this->dominion->military_spies = 5000;
        $this->target->military_spies = 5000;

        $hero = Hero::create([
            'dominion_id' => $this->dominion->id,
            'name' => 'Test Hero',
            'class' => 'alchemist',
            'experience' => 100,
            'class_data' => [],
        ]);

        $this->dominion->daily_xp = HeroCalculator::DAILY_OPS_XP_CAP;
        $statBefore = (int) $this->dominion->stat_espionage_success;
        $spyStrengthBefore = (float) $this->dominion->spy_strength;

        $result = $this->espionageActionService->performOperation($this->dominion, 'barracks_spy', $this->target);

        $this->assertTrue($result['success']);
        $hero->refresh();
        $this->assertEquals(HeroCalculator::DAILY_OPS_XP_CAP, (float) $this->dominion->daily_xp);
        $this->assertEquals(100, (float) $hero->experience);
        $this->assertEquals($statBefore + 1, (int) $this->dominion->stat_espionage_success);
        $this->assertLessThan($spyStrengthBefore, (float) $this->dominion->spy_strength);
    }

    public function testPerformOperation_DailyXp_ShrineBonus_DoesNotAccelerateCap()
    {
        global $mockRandomChance;
        $mockRandomChance = true;

        $this->dominion->military_spies = 5000;
        $this->target->military_spies = 5000;

        Hero::create([
            'dominion_id' => $this->dominion->id,
            'name' => 'Test Hero',
            'class' => 'alchemist',
            'experience' => 0,
            'class_data' => [],
        ]);

        // Stack shrines so getExperienceMultiplier hits its +200% cap. Hero XP
        // should jump, but daily_xp should still advance by raw 1.0.
        $this->dominion->building_shrine = 10000;
        $this->dominion->daily_xp = 0;

        $this->espionageActionService->performOperation($this->dominion, 'barracks_spy', $this->target);

        $this->assertEqualsWithDelta(1.0, (float) $this->dominion->daily_xp, 0.0001);
    }

    /**
     * Helper: total land across all land types for a dominion.
     */
    protected function totalLand(Dominion $dominion): int
    {
        return $dominion->land_plain
            + $dominion->land_cavern
            + $dominion->land_hill
            + $dominion->land_mountain
            + $dominion->land_swamp
            + $dominion->land_forest
            + $dominion->land_water;
    }
}
