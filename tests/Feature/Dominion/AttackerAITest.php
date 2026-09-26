<?php

namespace OpenDominion\Tests\Feature\Dominion;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Factories\DominionFactory;
use OpenDominion\Helpers\AIHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\Actions\InvadeActionService;
use OpenDominion\Services\Dominion\AIService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class AttackerAITest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    protected AIHelper $aiHelper;

    protected AIService $aiService;

    protected MilitaryCalculator $militaryCalculator;

    protected QueueService $queueService;

    protected Round $round;

    protected Realm $graveyard;

    protected Dominion $attacker;

    protected Dominion $target;

    protected function setUp(): void
    {
        parent::setUp();

        global $mockRandomChance;
        $mockRandomChance = false;

        // Actions are refused during the first seconds of each hour (tick), so never run these at :00
        request()->server->set('REQUEST_TIME', now()->setMinute(30)->timestamp);

        $this->aiHelper = $this->app->make(AIHelper::class);
        $this->aiService = $this->app->make(AIService::class);
        $this->militaryCalculator = $this->app->make(MilitaryCalculator::class);
        $this->queueService = $this->app->make(QueueService::class);

        $this->round = $this->createRound('-7 days');
        $this->round->update(['mixed_alignment' => true]);

        $this->graveyard = Realm::create([
            'round_id' => $this->round->id,
            'alignment' => 'neutral',
            'number' => 0,
            'name' => 'The Graveyard',
        ]);

        $orc = Race::where('name', 'Orc')->firstOrFail();
        $human = Race::where('name', 'Human')->firstOrFail();

        $this->attacker = $this->createBot($orc, 1000, [
            'military_unit1' => 10000,
            'military_unit3' => 8000,
            'resource_boats' => 1000,
        ]);
        $this->attacker->update([
            'ai_enabled' => true,
            'ai_config' => $this->aiHelper->generateAttackerConfig($orc),
        ]);

        $this->target = $this->createBot($human, 900, [
            'military_unit2' => 2000,
        ]);
    }

    protected function tearDown(): void
    {
        global $mockRandomChance;
        $mockRandomChance = null;

        parent::tearDown();
    }

    /**
     * Creates a bot in the graveyard with no military other than the given attributes.
     */
    protected function createBot(Race $race, int $landSize, array $attributes = []): Dominion
    {
        $dominionFactory = $this->app->make(DominionFactory::class);
        $dominion = $dominionFactory->createRandomNonPlayer($this->graveyard, $race, $landSize);
        $dominion->queues()->delete();

        $dominion->update(array_merge([
            'morale' => 100,
            'prestige' => 250,
            'military_draftees' => 0,
            'military_unit1' => 0,
            'military_unit2' => 0,
            'military_unit3' => 0,
            'military_unit4' => 0,
        ], $attributes));

        return $dominion->refresh();
    }

    public function testHomeGuardDefenseIgnoresOffensiveUnits(): void
    {
        $homeGuardDefense = $this->aiService->getHomeGuardDefense($this->attacker);

        $this->attacker->military_unit4 = 5000;
        $this->assertEquals($homeGuardDefense, $this->aiService->getHomeGuardDefense($this->attacker));
        $this->assertGreaterThan($homeGuardDefense, $this->militaryCalculator->getDefensivePower($this->attacker));

        $this->queueService->queueResources('training', $this->attacker, ['military_unit3' => 100], 9);
        $this->assertGreaterThan($homeGuardDefense, $this->aiService->getHomeGuardDefense($this->attacker->refresh()));
    }

    public function testUnitSwapAppliesAtPrestigeThreshold(): void
    {
        $config = $this->attacker->ai_config;

        $this->attacker->prestige = 599;
        $unchanged = $this->aiService->applyUnitSwap($this->attacker, $config);
        $this->assertEquals('unit3', $unchanged['military'][0]['unit']);
        $this->assertEquals('unit1', $unchanged['offense']);
        $this->assertArrayHasKey('unit_swap', $unchanged);

        $this->attacker->prestige = 600;
        $swapped = $this->aiService->applyUnitSwap($this->attacker, $config);
        $this->assertEquals('unit2', $swapped['military'][0]['unit']);
        $this->assertEquals('unit4', $swapped['offense']);
        $this->assertArrayNotHasKey('unit_swap', $swapped);
        $this->assertEquals($swapped, $this->attacker->refresh()->ai_config);
    }

    public function testTrainsDefenseBeforeOffense(): void
    {
        $landCalculator = $this->app->make(LandCalculator::class);
        $totalLand = $landCalculator->getTotalLandIncoming($this->attacker);
        $defenseRequired = $this->aiHelper->getDefenseForNonPlayer($this->round, $totalLand);

        $this->attacker->update([
            'military_unit1' => 0,
            'military_unit3' => 0,
            'military_draftees' => 2000,
            'resource_platinum' => 500000,
            'resource_lumber' => 500000,
            'resource_ore' => 500000,
        ]);
        $this->assertLessThan($defenseRequired, $this->aiService->getHomeGuardDefense($this->attacker));

        $this->aiService->trainAttackerMilitary($this->attacker, $this->attacker->ai_config, $totalLand);
        $this->attacker->refresh();
        $this->assertGreaterThan(0, $this->queueService->getTrainingQueueTotalByResource($this->attacker, 'military_unit3'));
        $this->assertEquals(0, $this->queueService->getTrainingQueueTotalByResource($this->attacker, 'military_unit1'));

        $this->attacker->queues()->delete();
        $this->attacker->update([
            'military_unit3' => 20000,
            'military_draftees' => 2000,
            'resource_platinum' => 500000,
        ]);
        $this->assertGreaterThanOrEqual($defenseRequired, $this->aiService->getHomeGuardDefense($this->attacker->refresh()));

        $this->aiService->trainAttackerMilitary($this->attacker, $this->attacker->ai_config, $totalLand);
        $this->attacker->refresh();
        $this->assertGreaterThan(0, $this->queueService->getTrainingQueueTotalByResource($this->attacker, 'military_unit1'));
        $this->assertEquals(0, $this->queueService->getTrainingQueueTotalByResource($this->attacker, 'military_unit3'));
    }

    public function testSurplusDrafteesAreReleasedDownToReserve(): void
    {
        $this->attacker->update(['military_draftees' => 2000]);
        $this->aiService->releaseSurplusDraftees($this->attacker->refresh());
        $this->assertEquals(2000, $this->attacker->refresh()->military_draftees);

        $this->attacker->update(['military_draftees' => 2001]);
        $this->aiService->releaseSurplusDraftees($this->attacker->refresh());
        $this->assertEquals(1500, $this->attacker->refresh()->military_draftees);

        $this->attacker->update(['military_draftees' => 9000]);
        $this->aiService->releaseSurplusDraftees($this->attacker->refresh());
        $this->assertEquals(1500, $this->attacker->refresh()->military_draftees);
    }

    public function testAttackerActionsReleaseSurplusDraftees(): void
    {
        $this->attacker->update([
            'military_draftees' => 5000,
            'resource_platinum' => 0,
            'resource_lumber' => 0,
            'resource_ore' => 0,
        ]);

        $this->aiService->performAttackerActions($this->attacker->refresh(), $this->attacker->ai_config);

        $this->assertEquals(1500, $this->attacker->refresh()->military_draftees);
    }

    public function testRezonesBarrenLandTheBuildPlanDoesNotUse(): void
    {
        $landCalculator = $this->app->make(LandCalculator::class);
        $this->attacker->update([
            'land_mountain' => $this->attacker->land_mountain + 50,
            'resource_platinum' => 1000000,
        ]);
        $this->attacker->refresh();
        $this->assertEquals(50, $landCalculator->getBarrenLandByLandType($this->attacker)['mountain']);

        $totalLand = $landCalculator->getTotalLandIncoming($this->attacker);
        $this->aiService->rezoneForBuildPlan($this->attacker, $this->attacker->ai_config, $totalLand);

        $this->attacker->refresh();
        $this->assertEquals(0, $landCalculator->getBarrenLandByLandType($this->attacker)['mountain']);
        $this->assertEquals($totalLand, $landCalculator->getTotalLandIncoming($this->attacker));
    }

    public function testUnitsToSendIsSmallestForceThatBreaksTarget(): void
    {
        $rangeCalculator = $this->app->make(RangeCalculator::class);
        $landRatio = $rangeCalculator->getDominionRange($this->attacker, $this->target) / 100;
        $targetDefense = $this->militaryCalculator->getDefensivePowerWithTemples($this->attacker, $this->target);
        $availableUnits = $this->aiService->getAvailableOffensiveUnits($this->attacker);

        $units = $this->aiService->getUnitsToSend($this->attacker, $this->target, $landRatio, $availableUnits, $targetDefense);

        $this->assertEquals([1], array_keys($units));
        $this->assertGreaterThan($targetDefense, $this->militaryCalculator->getOffensivePower($this->attacker, $this->target, $landRatio, $units));
        $this->assertLessThanOrEqual($targetDefense, $this->militaryCalculator->getOffensivePower($this->attacker, $this->target, $landRatio, [1 => $units[1] - 1]));

        $this->assertNull($this->aiService->getUnitsToSend($this->attacker, $this->target, $landRatio, [1 => 10], $targetDefense));
    }

    public function testAvailableOffensiveUnitsAreLimitedByBoats(): void
    {
        $boatCapacity = $this->militaryCalculator->getBoatCapacity($this->attacker);
        $this->attacker->resource_boats = 10;

        $this->assertEquals([1 => 10 * $boatCapacity, 4 => 0], $this->aiService->getAvailableOffensiveUnits($this->attacker));
    }

    public function testAttackerInvadesBotInGraveyard(): void
    {
        $this->assertTrue($this->aiService->attemptInvasion($this->attacker, $this->attacker->ai_config));

        $invasion = GameEvent::where('type', 'invasion')
            ->where('source_id', $this->attacker->id)
            ->where('target_id', $this->target->id)
            ->first();
        $this->assertNotNull($invasion);
        $this->assertTrue($invasion->data['result']['success']);
        $this->assertLessThan(10000, $this->attacker->refresh()->military_unit1);
    }

    public function testAttackSpellsAreCastOnlyWhenLookingForTargets(): void
    {
        $spellCalculator = $this->app->make(SpellCalculator::class);

        $this->aiService->castSpells($this->attacker, $this->attacker->ai_config);
        $this->assertFalse($spellCalculator->isSpellActive($this->attacker->refresh(), 'bloodrage'));

        $this->attacker->update(['military_unit1' => 100]);
        $this->aiService->attemptInvasion($this->attacker, $this->attacker->ai_config);
        $this->assertFalse($spellCalculator->isSpellActive($this->attacker->refresh(), 'bloodrage'));

        $this->attacker->update(['military_unit1' => 10000]);
        $this->assertTrue($this->aiService->attemptInvasion($this->attacker, $this->attacker->ai_config));
        $this->assertTrue($spellCalculator->isSpellActive($this->attacker->refresh(), 'bloodrage'));
    }

    public function testAttackerSkipsTargetsItCannotBreak(): void
    {
        $this->target->update(['military_unit2' => 50000]);

        $this->assertFalse($this->aiService->attemptInvasion($this->attacker, $this->attacker->ai_config));
        $this->assertEquals(0, GameEvent::where('type', 'invasion')->where('source_id', $this->attacker->id)->count());
    }

    public function testAttackerSkipsTargetsBelowMinimumRange(): void
    {
        $this->target->update(['land_plain' => $this->target->land_plain - 300]);

        $this->assertFalse($this->aiService->attemptInvasion($this->attacker, $this->attacker->ai_config));
        $this->assertEquals(0, GameEvent::where('type', 'invasion')->where('source_id', $this->attacker->id)->count());
    }

    public function testAttackerSkipsTargetSearchWithoutEnoughOffense(): void
    {
        $this->attacker->update(['military_unit1' => 100]);

        $this->assertFalse($this->aiService->attemptInvasion($this->attacker, $this->attacker->ai_config));
    }

    public function testInvasionPreCheckIsSkippedOnDayOne(): void
    {
        $this->target->update(['military_unit2' => 0]);
        $this->attacker->update(['military_unit1' => 2000]);

        $this->assertFalse($this->aiService->attemptInvasion($this->attacker, $this->attacker->ai_config));

        $this->round->update(['start_date' => now()->subHours(1)->startOfHour()]);

        $this->assertTrue($this->aiService->attemptInvasion($this->attacker->refresh()->load('round'), $this->attacker->ai_config));
    }

    public function testAttackerWaitsForReturningUnits(): void
    {
        $this->queueService->queueResources('invasion', $this->attacker, ['military_unit1' => 100], 6);

        $this->assertFalse($this->aiService->attemptInvasion($this->attacker->refresh(), $this->attacker->ai_config));
        $this->assertEquals(0, GameEvent::where('type', 'invasion')->where('source_id', $this->attacker->id)->count());
    }

    public function testAttackerNeverTargetsHumans(): void
    {
        $this->target->update(['user_id' => $this->createUser()->id]);

        $this->assertFalse($this->aiService->attemptInvasion($this->attacker, $this->attacker->ai_config));
        $this->assertEquals(0, GameEvent::where('type', 'invasion')->where('source_id', $this->attacker->id)->count());
    }

    public function testInvasionMinuteAvoidsTickAndHourlyRun(): void
    {
        $allowedMinutes = collect(AIService::INVASION_MINUTES);
        $hour = now()->startOfHour();

        $minutes = collect(range(0, 47))->map(fn (int $hours) => $this->aiService->getInvasionMinute($this->attacker, $hour->copy()->addHours($hours)));

        $this->assertTrue($minutes->every(fn (int $minute) => $allowedMinutes->contains($minute)));
        $this->assertGreaterThan(1, $minutes->unique()->count());
        $this->assertEquals($minutes[0], $this->aiService->getInvasionMinute($this->attacker, $hour->copy()->addMinutes(59)));
    }

    public function testScheduledInvasionsOnlyRunAtTheAttackersMinute(): void
    {
        $hour = now()->startOfHour();
        $minute = $this->aiService->getInvasionMinute($this->attacker, $hour);
        $otherMinute = $minute === 5 ? 10 : 5;

        $this->aiService->executeRoundInvasions($this->round, $hour->copy()->setMinute($otherMinute));
        $this->aiService->executeRoundInvasions($this->round, $hour->copy()->setMinute($minute + 1));
        $this->assertEquals(0, GameEvent::where('type', 'invasion')->where('source_id', $this->attacker->id)->count());

        $this->aiService->executeRoundInvasions($this->round, $hour->copy()->setMinute($minute));
        $this->assertEquals(1, GameEvent::where('type', 'invasion')->where('source_id', $this->attacker->id)->count());
    }

    public function testHourlyAttackerActionsDoNotInvade(): void
    {
        $this->aiService->performActions($this->attacker);

        $this->assertEquals(0, GameEvent::where('type', 'invasion')->where('source_id', $this->attacker->id)->count());
    }

    public function testExplorerBotsDoNotInvade(): void
    {
        $this->attacker->update(['ai_config' => $this->aiHelper->generateConfig($this->attacker->race)]);

        $this->aiService->performActions($this->attacker->refresh());

        $this->assertEquals(0, GameEvent::where('type', 'invasion')->where('source_id', $this->attacker->id)->count());
    }

    public function testConsecutiveInvasionsDoNotShareResults(): void
    {
        $invadeActionService = $this->app->make(InvadeActionService::class);
        $secondAttacker = $this->createBot($this->attacker->race, 1000, [
            'military_unit1' => 10000,
            'military_unit3' => 8000,
            'resource_boats' => 1000,
        ]);
        $secondTarget = $this->createBot($this->target->race, 900, ['military_unit2' => 2000]);

        $invadeActionService->invade($this->attacker, $this->target, [1 => 5000], false);
        $invadeActionService->invade($secondAttacker, $secondTarget, [1 => 5000], false);

        $events = GameEvent::where('type', 'invasion')
            ->whereIn('source_id', [$this->attacker->id, $secondAttacker->id])
            ->get();
        $this->assertCount(2, $events);
        foreach ($events as $event) {
            $this->assertEquals($event->data['defender']['landLost'], array_sum($event->data['attacker']['landConquered']));
        }
        $this->assertEquals(
            array_sum($events->firstWhere('source_id', $secondAttacker->id)->data['attacker']['landConquered']),
            $secondTarget->refresh()->stat_total_land_lost
        );
    }

    public function testRealmmatesCanOnlyInvadeEachOtherInGraveyard(): void
    {
        $invadeActionService = $this->app->make(InvadeActionService::class);
        $invadeActionService->invade($this->attacker, $this->target, [1 => 5000], false);

        $this->assertEquals(1, GameEvent::where('type', 'invasion')->where('source_id', $this->attacker->id)->count());
    }
}
