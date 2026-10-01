<?php

namespace OpenDominion\Tests\Feature;

use OpenDominion\Calculators\Dominion\Actions\ConstructionCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\RealmWar;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundPerk;
use OpenDominion\Services\RoundPerkService;
use OpenDominion\Tests\AbstractTestCase;

class RoundPerkTest extends AbstractTestCase
{
    protected Round $round;

    protected Dominion $goodDominion;

    protected Dominion $evilDominion;

    protected function setUp(): void
    {
        parent::setUp();

        // Day 6 of the round
        $this->round = $this->createRound('-5 days');

        $this->goodDominion = $this->createDominion(
            $this->createUser(),
            $this->round,
            Race::where('key', 'human')->firstOrFail()
        );
        $this->evilDominion = $this->createDominion(
            $this->createUser(),
            $this->round,
            Race::where('alignment', 'evil')->where('playable', true)->firstOrFail()
        );
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function createPerk(array $attributes): RoundPerk
    {
        $perk = app(RoundPerkService::class)->create($this->round, $attributes);

        $this->goodDominion->round->load('perks');
        $this->evilDominion->round->load('perks');

        return $perk;
    }

    public function testPerkWithoutConditionsAppliesToEveryone(): void
    {
        $this->createPerk(['key' => 'offense', 'value' => '5']);

        $this->assertSame(5.0, $this->goodDominion->getRoundPerkValue('offense'));
        $this->assertSame(5.0, $this->evilDominion->getRoundPerkValue('offense'));
        $this->assertSame(0.05, $this->goodDominion->getRoundPerkMultiplier('offense'));
    }

    public function testMissingPerkReturnsZero(): void
    {
        $this->assertSame(0.0, $this->goodDominion->getRoundPerkValue('offense'));
    }

    public function testAlignmentPerkOnlyAppliesToMatchingRaces(): void
    {
        $this->createPerk(['key' => 'offense', 'value' => '5', 'alignment' => 'evil']);

        $this->assertSame(0.0, $this->goodDominion->getRoundPerkValue('offense'));
        $this->assertSame(5.0, $this->evilDominion->getRoundPerkValue('offense'));
    }

    public function testDayBoundariesAreInclusive(): void
    {
        $perk = new RoundPerk(['from_day' => 3, 'until_day' => 6]);

        $this->assertFalse($perk->isActiveOnDay(2));
        $this->assertTrue($perk->isActiveOnDay(3));
        $this->assertTrue($perk->isActiveOnDay(6));
        $this->assertFalse($perk->isActiveOnDay(7));
    }

    public function testExpiredAndUpcomingPerksDoNotApply(): void
    {
        $this->createPerk(['key' => 'offense', 'value' => '5', 'until_day' => 5]);
        $this->createPerk(['key' => 'offense', 'value' => '7', 'from_day' => 7]);
        $this->createPerk(['key' => 'offense', 'value' => '3', 'from_day' => 6, 'until_day' => 6]);

        $this->assertSame(3.0, $this->goodDominion->getRoundPerkValue('offense'));
    }

    public function testMatchingPerksAreSummed(): void
    {
        $this->createPerk(['key' => 'defense', 'value' => '5']);
        $this->createPerk(['key' => 'defense', 'value' => '-2.5']);

        $this->assertSame(2.5, $this->goodDominion->getRoundPerkValue('defense'));
    }

    public function testCompoundValueIsSplitIntoParts(): void
    {
        $this->createPerk(['key' => 'offense', 'value' => 'tower, 5']);

        $this->assertSame(['tower', '5'], $this->goodDominion->getRoundPerkValueParts('offense'));
        $this->assertNull($this->goodDominion->getRoundPerkValueParts('defense'));
    }

    public function testPerksFromOtherRoundsAreIgnored(): void
    {
        $otherRound = $this->createRound('-5 days');
        app(RoundPerkService::class)->create($otherRound, ['key' => 'offense', 'value' => '5']);

        $this->assertSame(0.0, $this->goodDominion->getRoundPerkValue('offense'));
    }

    public function testOffensivePowerMultiplierIncludesRoundPerk(): void
    {
        $militaryCalculator = app(MilitaryCalculator::class);
        $baseline = $militaryCalculator->getOffensivePowerMultiplier($this->goodDominion);

        $this->createPerk(['key' => 'offense', 'value' => '5']);

        $this->assertEqualsWithDelta($baseline + 0.05, $militaryCalculator->getOffensivePowerMultiplier($this->goodDominion), 0.0001);
    }

    public function testDefensivePowerMultiplierIncludesRoundPerk(): void
    {
        $militaryCalculator = app(MilitaryCalculator::class);
        $baseline = $militaryCalculator->getDefensivePowerMultiplier($this->goodDominion);

        $this->createPerk(['key' => 'defense', 'value' => '5']);

        $this->assertEqualsWithDelta($baseline + 0.05, $militaryCalculator->getDefensivePowerMultiplier($this->goodDominion), 0.0001);
    }

    public function testConstructionCostMultiplierIncludesRoundPerk(): void
    {
        $constructionCalculator = app(ConstructionCalculator::class);
        $baseline = $constructionCalculator->getPlatinumCostMultiplier($this->goodDominion);

        $this->createPerk(['key' => 'construction_cost', 'value' => '-10']);

        $this->assertLessThan($baseline, $constructionCalculator->getPlatinumCostMultiplier($this->goodDominion));
    }

    public function testTechProductionMultiplierIncludesRoundPerk(): void
    {
        $productionCalculator = app(ProductionCalculator::class);
        $baseline = $productionCalculator->getTechProductionMultiplier($this->goodDominion);

        $this->createPerk(['key' => 'tech_production', 'value' => '10']);

        $this->assertEqualsWithDelta($baseline + 0.10, $productionCalculator->getTechProductionMultiplier($this->goodDominion), 0.0001);
    }

    public function testWartimePlatinumPerkOnlyAppliesWhileAtWar(): void
    {
        $productionCalculator = app(ProductionCalculator::class);
        $baseline = $productionCalculator->getPlatinumProductionMultiplier($this->goodDominion);

        $this->createPerk(['key' => 'wartime_platinum_production', 'value' => '10']);

        $this->assertEqualsWithDelta($baseline, $productionCalculator->getPlatinumProductionMultiplier($this->goodDominion), 0.0001);

        RealmWar::create([
            'source_realm_id' => $this->evilDominion->realm_id,
            'target_realm_id' => $this->goodDominion->realm_id,
        ]);

        $this->assertEqualsWithDelta($baseline + 0.10, $productionCalculator->getPlatinumProductionMultiplier($this->goodDominion), 0.0001);
    }

    public function testWartimePlatinumPerkAppliesOnceForMultipleWars(): void
    {
        $productionCalculator = app(ProductionCalculator::class);
        $baseline = $productionCalculator->getPlatinumProductionMultiplier($this->goodDominion);

        $this->createPerk(['key' => 'wartime_platinum_production', 'value' => '10']);
        $otherRealm = $this->createRealm($this->round, 'evil');

        RealmWar::create(['source_realm_id' => $this->goodDominion->realm_id, 'target_realm_id' => $this->evilDominion->realm_id]);
        RealmWar::create(['source_realm_id' => $otherRealm->id, 'target_realm_id' => $this->goodDominion->realm_id]);

        $this->assertEqualsWithDelta($baseline + 0.10, $productionCalculator->getPlatinumProductionMultiplier($this->goodDominion), 0.0001);
    }

    public function testWartimePlatinumPerkIgnoresEndedWars(): void
    {
        $productionCalculator = app(ProductionCalculator::class);
        $baseline = $productionCalculator->getPlatinumProductionMultiplier($this->goodDominion);

        $this->createPerk(['key' => 'wartime_platinum_production', 'value' => '10']);

        RealmWar::create([
            'source_realm_id' => $this->goodDominion->realm_id,
            'target_realm_id' => $this->evilDominion->realm_id,
            'inactive_at' => now()->subDay(),
        ]);

        $this->assertEqualsWithDelta($baseline, $productionCalculator->getPlatinumProductionMultiplier($this->goodDominion), 0.0001);
    }

    public function testInvestmentMultiplierIncludesRoundPerk(): void
    {
        $improvementCalculator = app(ImprovementCalculator::class);
        $baseline = $improvementCalculator->getInvestmentMultiplier($this->goodDominion);

        $this->createPerk(['key' => 'invest_bonus', 'value' => '10']);

        $this->assertEqualsWithDelta($baseline + 0.10, $improvementCalculator->getInvestmentMultiplier($this->goodDominion), 0.0001);
    }

    public function testTowerManaPerkIncreasesRawManaPerTower(): void
    {
        $this->goodDominion->building_tower = 20;
        $productionCalculator = app(ProductionCalculator::class);
        $baseline = $productionCalculator->getManaProductionRaw($this->goodDominion);

        $this->createPerk(['key' => 'tower_mana_production_raw', 'value' => '5']);

        $this->assertEqualsWithDelta($baseline + 100, $productionCalculator->getManaProductionRaw($this->goodDominion), 0.0001);
    }

    public function testAlchemyPlatinumPerkIncreasesRawPlatinumPerAlchemy(): void
    {
        $this->goodDominion->building_alchemy = 10;
        $productionCalculator = app(ProductionCalculator::class);
        $baseline = $productionCalculator->getPlatinumProductionRaw($this->goodDominion);

        $this->createPerk(['key' => 'alchemy_platinum_production_raw', 'value' => '15']);

        $this->assertEqualsWithDelta($baseline + 150, $productionCalculator->getPlatinumProductionRaw($this->goodDominion), 0.0001);
    }

    public function testFarmFoodPerkIncreasesRawFoodPerFarm(): void
    {
        $this->goodDominion->building_farm = 10;
        $productionCalculator = app(ProductionCalculator::class);
        $baseline = $productionCalculator->getFoodProductionRaw($this->goodDominion);

        $this->createPerk(['key' => 'farm_food_production_raw', 'value' => '20']);

        $this->assertEqualsWithDelta($baseline + 200, $productionCalculator->getFoodProductionRaw($this->goodDominion), 0.0001);
    }

    public function testServiceReportsStatusForEachPerk(): void
    {
        $this->createPerk(['key' => 'offense', 'value' => '5', 'name' => 'A']);
        $this->createPerk(['key' => 'offense', 'value' => '5', 'name' => 'B', 'alignment' => 'evil']);
        $this->createPerk(['key' => 'offense', 'value' => '5', 'name' => 'C', 'until_day' => 2]);
        $this->createPerk(['key' => 'offense', 'value' => '5', 'name' => 'D', 'from_day' => 10]);

        $statuses = app(RoundPerkService::class)
            ->getPerksForDominion($this->goodDominion)
            ->mapWithKeys(fn (array $row) => [$row['perk']->name => $row['status']])
            ->all();

        $this->assertSame([
            'A' => RoundPerkService::STATUS_ACTIVE,
            'B' => RoundPerkService::STATUS_NOT_APPLICABLE,
            'C' => RoundPerkService::STATUS_EXPIRED,
            'D' => RoundPerkService::STATUS_UPCOMING,
        ], $statuses);
    }
}
