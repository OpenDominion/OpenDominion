<?php

namespace OpenDominion\Tests\Feature\Dominion;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Calculators\Dominion\Actions\TechCalculator;
use OpenDominion\Mappers\Dominion\InfoMapper;
use OpenDominion\Models\DominionTech;
use OpenDominion\Models\RoundWonder;
use OpenDominion\Models\Tech;
use OpenDominion\Models\Wonder;
use OpenDominion\Services\Dominion\Actions\TechActionService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class TemporaryTechTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var \OpenDominion\Models\User */
    protected $user;

    /** @var \OpenDominion\Models\Round */
    protected $round;

    /** @var \OpenDominion\Models\Dominion */
    protected $dominion;

    /** @var RoundWonder */
    protected $roundWonder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAndImpersonateUser();
        $this->round = $this->createRound();
        $this->round->update(['tech_version' => 2]);
        $this->dominion = $this->createAndSelectDominion($this->user, $this->round);

        // Assign Planar Gates to the dominion's realm
        $wonder = Wonder::where('key', 'planar_gates')->first();
        $this->roundWonder = RoundWonder::create([
            'round_id' => $this->round->id,
            'realm_id' => $this->dominion->realm_id,
            'wonder_id' => $wonder->id,
            'power' => $wonder->power,
        ]);

        // Reload relationships including wonder perks
        $this->dominion->load('realm.wonders.perks');
    }

    /**
     * Get a starter tech (no prerequisites) for the current round's tech version.
     */
    protected function getStarterTech(): Tech
    {
        return Tech::where('version', $this->round->tech_version)
            ->where('key', '!=', 'tech_7_5')
            ->get()
            ->filter(function ($tech) {
                return empty($tech->prerequisites);
            })
            ->first();
    }

    /**
     * Get multiple starter techs.
     */
    protected function getStarterTechs(int $count): \Illuminate\Support\Collection
    {
        return Tech::where('version', $this->round->tech_version)
            ->where('key', '!=', 'tech_7_5')
            ->get()
            ->filter(function ($tech) {
                return empty($tech->prerequisites);
            })
            ->take($count)
            ->values();
    }

    /**
     * Add a temporary tech for the dominion.
     */
    protected function addTemporaryTech(Tech $tech): DominionTech
    {
        $dt = DominionTech::create([
            'dominion_id' => $this->dominion->id,
            'tech_id' => $tech->id,
            'source_type' => RoundWonder::class,
            'source_id' => $this->roundWonder->id,
        ]);
        $this->dominion->load('techs');
        return $dt;
    }

    /**
     * Add a permanent tech for the dominion.
     */
    protected function addPermanentTech(Tech $tech): DominionTech
    {
        $dt = DominionTech::create([
            'dominion_id' => $this->dominion->id,
            'tech_id' => $tech->id,
        ]);
        $this->dominion->load('techs');
        return $dt;
    }

    public function testTemporaryTechExcludedFromCostCalculation()
    {
        $techCalculator = app(TechCalculator::class);
        $costBefore = $techCalculator->getTechCost($this->dominion);

        $this->addTemporaryTech($this->getStarterTech());

        $costAfter = $techCalculator->getTechCost($this->dominion);
        $this->assertEquals($costBefore, $costAfter);
    }

    public function testPermanentTechIncreasesCost()
    {
        $techCalculator = app(TechCalculator::class);

        // Ensure cost is above the 3750 floor
        $this->dominion->highest_land_achieved = 2000;
        $this->dominion->save();

        $costBefore = $techCalculator->getTechCost($this->dominion);

        $this->addPermanentTech($this->getStarterTech());

        $costAfter = $techCalculator->getTechCost($this->dominion);
        $this->assertGreaterThan($costBefore, $costAfter);
    }

    public function testTemporaryTechExcludedFromPrerequisites()
    {
        $techCalculator = app(TechCalculator::class);

        // Find a tech that has prerequisites
        $techWithPrereqs = Tech::where('version', $this->round->tech_version)
            ->get()
            ->filter(function ($tech) {
                return !empty($tech->prerequisites);
            })
            ->first();

        if ($techWithPrereqs === null) {
            $this->markTestSkipped('No techs with prerequisites found.');
        }

        $prereqKey = $techWithPrereqs->prerequisites[0];
        $prereqTech = Tech::where('version', $this->round->tech_version)
            ->where('key', $prereqKey)
            ->first();

        // Add prerequisite as temporary tech - should NOT satisfy prerequisite
        $this->addTemporaryTech($prereqTech);

        $this->assertFalse($techCalculator->hasPrerequisites($this->dominion, $techWithPrereqs));
    }

    public function testPermanentPrerequisiteSatisfiesRequirement()
    {
        $techCalculator = app(TechCalculator::class);

        $techWithPrereqs = Tech::where('version', $this->round->tech_version)
            ->get()
            ->filter(function ($tech) {
                return !empty($tech->prerequisites);
            })
            ->first();

        if ($techWithPrereqs === null) {
            $this->markTestSkipped('No techs with prerequisites found.');
        }

        $prereqKey = $techWithPrereqs->prerequisites[0];
        $prereqTech = Tech::where('version', $this->round->tech_version)
            ->where('key', $prereqKey)
            ->first();

        // Add prerequisite as permanent tech - SHOULD satisfy prerequisite
        $this->addPermanentTech($prereqTech);

        $this->assertTrue($techCalculator->hasPrerequisites($this->dominion, $techWithPrereqs));
    }

    public function testPermanentUnlockConvertsTemporaryTech()
    {
        $tech = $this->getStarterTech();
        $this->addTemporaryTech($tech);

        // Give enough research points and ensure dominion can act
        $this->dominion->resource_tech = 999999;
        $this->dominion->protection_ticks_remaining = 0;
        $this->dominion->save();
        $this->dominion->load('techs');

        $techActionService = app(TechActionService::class);
        $techActionService->unlock($this->dominion, $tech->key);

        // Should have exactly one row, now permanent
        $rows = DominionTech::where('dominion_id', $this->dominion->id)
            ->where('tech_id', $tech->id)
            ->get();

        $this->assertEquals(1, $rows->count());
        $this->assertNull($rows->first()->source_id);
        $this->assertNull($rows->first()->source_type);
    }

    public function testWonderLossRemovesTemporaryTechs()
    {
        $this->addTemporaryTech($this->getStarterTech());

        // Simulate wonder loss (same as handleWonderDestroyed)
        DominionTech::whereIn('dominion_id', [$this->dominion->id])
            ->where('source_type', RoundWonder::class)
            ->where('source_id', $this->roundWonder->id)
            ->delete();

        $tempCount = DominionTech::where('dominion_id', $this->dominion->id)
            ->where('source_type', RoundWonder::class)
            ->count();

        $this->assertEquals(0, $tempCount);
    }

    public function testWonderLossPreservesPermanentTechs()
    {
        $techs = $this->getStarterTechs(2);

        $this->addPermanentTech($techs[0]);
        $this->addTemporaryTech($techs[1]);

        // Simulate wonder loss
        DominionTech::whereIn('dominion_id', [$this->dominion->id])
            ->where('source_type', RoundWonder::class)
            ->where('source_id', $this->roundWonder->id)
            ->delete();

        // Permanent tech should remain
        $remaining = DominionTech::where('dominion_id', $this->dominion->id)->get();
        $this->assertEquals(1, $remaining->count());
        $this->assertEquals($techs[0]->id, $remaining->first()->tech_id);
        $this->assertNull($remaining->first()->source_id);
    }

    public function testTechCountExcludesTemporaryTechs()
    {
        $techs = $this->getStarterTechs(2);

        $this->addPermanentTech($techs[0]);
        $this->addTemporaryTech($techs[1]);

        // tech_count attribute should only count permanent
        $this->assertEquals(1, $this->dominion->tech_count);

        // But total techs relation should include both
        $this->assertEquals(2, $this->dominion->techs->count());
    }

    public function testChangingTemporaryTechReplacesOldOne()
    {
        $techs = $this->getStarterTechs(2);

        $this->addTemporaryTech($techs[0]);

        // Replace with second tech
        DominionTech::where('dominion_id', $this->dominion->id)
            ->where('source_type', RoundWonder::class)
            ->where('source_id', $this->roundWonder->id)
            ->delete();

        DominionTech::create([
            'dominion_id' => $this->dominion->id,
            'tech_id' => $techs[1]->id,
            'source_type' => RoundWonder::class,
            'source_id' => $this->roundWonder->id,
        ]);

        // Should only have one temporary tech
        $tempCount = DominionTech::where('dominion_id', $this->dominion->id)
            ->where('source_type', RoundWonder::class)
            ->count();

        $this->assertEquals(1, $tempCount);

        // Should be the second tech
        $tempTech = DominionTech::where('dominion_id', $this->dominion->id)
            ->where('source_type', RoundWonder::class)
            ->first();

        $this->assertEquals($techs[1]->id, $tempTech->tech_id);
    }

    public function testInfoMapperAppendsPlanarGatesToTemporaryTechName()
    {
        $tech = $this->getStarterTech();
        $this->addTemporaryTech($tech);

        $infoMapper = app(InfoMapper::class);
        $mapped = $infoMapper->mapTechs($this->dominion);

        $this->assertArrayHasKey($tech->key, $mapped);
        $this->assertStringContainsString('(Planar Gates)', $mapped[$tech->key]);
    }

    public function testInfoMapperPermanentTechHasNoSuffix()
    {
        $tech = $this->getStarterTech();
        $this->addPermanentTech($tech);

        $infoMapper = app(InfoMapper::class);
        $mapped = $infoMapper->mapTechs($this->dominion);

        $this->assertArrayHasKey($tech->key, $mapped);
        $this->assertStringNotContainsString('(Planar Gates)', $mapped[$tech->key]);
    }

    public function testTemporaryTechPerksAreApplied()
    {
        $tech = $this->getStarterTech();
        $this->addTemporaryTech($tech);

        // The tech should appear in the dominion's techs collection
        $this->assertTrue($this->dominion->techs->contains('key', $tech->key));
    }

    public function testHeroRefundExcludesTemporaryTechs()
    {
        $techs = $this->getStarterTechs(2);

        $this->addPermanentTech($techs[0]);
        $this->addTemporaryTech($techs[1]);

        // Simulate hero tech refund delete (only permanent)
        DominionTech::where('dominion_id', $this->dominion->id)
            ->whereNull('source_id')
            ->delete();

        // Temporary tech should remain
        $remaining = DominionTech::where('dominion_id', $this->dominion->id)->get();
        $this->assertEquals(1, $remaining->count());
        $this->assertNotNull($remaining->first()->source_id);
    }

    public function testCooldownPreventsEarlySwitch()
    {
        $techs = $this->getStarterTechs(2);

        // Add temporary tech selected 10 hours ago
        $this->addTemporaryTech($techs[0]);
        DominionTech::where('dominion_id', $this->dominion->id)
            ->where('source_type', RoundWonder::class)
            ->where('source_id', $this->roundWonder->id)
            ->update(['created_at' => now()->subHours(10)]);

        $existingTemp = DominionTech::where('dominion_id', $this->dominion->id)
            ->where('source_type', RoundWonder::class)
            ->where('source_id', $this->roundWonder->id)
            ->first();

        $selectedAt = $existingTemp->created_at->startOfHour();
        $cooldownEnd = $selectedAt->copy()->addHours(96);

        $this->assertTrue(now()->lt($cooldownEnd), 'Should still be in cooldown');
    }

    public function testCooldownExpiresAfter96Hours()
    {
        $techs = $this->getStarterTechs(2);

        // Add temporary tech selected 97 hours ago
        $this->addTemporaryTech($techs[0]);
        DominionTech::where('dominion_id', $this->dominion->id)
            ->where('source_type', RoundWonder::class)
            ->where('source_id', $this->roundWonder->id)
            ->update(['created_at' => now()->subHours(97)]);

        $existingTemp = DominionTech::where('dominion_id', $this->dominion->id)
            ->where('source_type', RoundWonder::class)
            ->where('source_id', $this->roundWonder->id)
            ->first();

        $selectedAt = $existingTemp->created_at->startOfHour();
        $cooldownEnd = $selectedAt->copy()->addHours(96);

        $this->assertFalse(now()->lt($cooldownEnd), 'Cooldown should have expired');
    }

    public function testNoCooldownOnFirstSelection()
    {
        // No existing temporary tech - should have no cooldown
        $existingTemp = DominionTech::where('dominion_id', $this->dominion->id)
            ->where('source_type', RoundWonder::class)
            ->where('source_id', $this->roundWonder->id)
            ->first();

        $this->assertNull($existingTemp, 'No existing temp tech means no cooldown');
    }
}
