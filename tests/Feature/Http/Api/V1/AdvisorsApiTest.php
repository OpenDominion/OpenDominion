<?php

namespace OpenDominion\Tests\Feature\Http\Api\V1;

use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Testing\TestResponse;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\InfoOp;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Tests\AbstractTestCase;

class AdvisorsApiTest extends AbstractTestCase
{
    private const OP_TYPES = ['clear_sight', 'revelation', 'castle_spy', 'barracks_spy', 'survey_dominion', 'land_spy', 'vision', 'disclosure'];

    private Round $round;
    private Dominion $dominion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);

        $this->round = $this->createRound();
        $this->dominion = $this->createDominion($this->createUser(), $this->round);
        $this->dominion->update(['api_key' => 'advisors-key', 'protection_finished' => true]);
    }

    public function testRequiresApiKey(): void
    {
        $this->getJson('/api/v1/dominions/me/advisors')
            ->assertStatus(401)
            ->assertJson(['error' => 'missing_api_key']);
    }

    public function testOwnDominionIsListedWithRealmAndAdvisorOps(): void
    {
        $response = $this->getAdvisors();

        $this->assertSame(['generated_at', 'realm', 'dominions'], array_keys($response->json()));
        $this->assertSame([
            'id' => $this->dominion->realm->id,
            'number' => $this->dominion->realm->number,
            'name' => $this->dominion->realm->name,
        ], $response->json('realm'));
        $this->assertSame([(string) $this->dominion->id], array_map('strval', array_keys($response->json('dominions'))));

        $entry = $response->json('dominions.' . $this->dominion->id);
        $this->assertSame(
            ['id', 'name', 'race', 'ops', 'resources', 'returning', 'military', 'land', 'buildings', 'hourly', 'population', 'statistics'],
            array_keys($entry)
        );
        $this->assertStringContainsString('"returning":{}', $response->getContent());
        $this->assertSame($this->dominion->id, $entry['id']);
        $this->assertSame($this->dominion->race->name, $entry['race']);
        $this->assertSame(self::OP_TYPES, array_keys($entry['ops']));
        foreach ($entry['ops'] as $type => $op) {
            $this->assertNotNull($op, $type . ' should come from advisors');
            $this->assertSame($response->json('generated_at'), $op['created_at']);
        }
        $this->assertSame($this->dominion->name, $entry['ops']['clear_sight']['name']);
        $this->assertArrayHasKey('techs', $entry['ops']['vision']);
        $this->assertArrayHasKey('spells', $entry['ops']['revelation']);
    }

    public function testEntryIncludesResourcesMilitaryProductionAndPopulation(): void
    {
        $this->dominion->update([
            'resource_platinum' => 123456,
            'resource_food' => 54321,
            'resource_boats' => 12.3456,
            'draft_rate' => 35,
            'military_draftees' => 1500,
            'military_unit1' => 11,
            'military_unit2' => 22,
            'military_unit3' => 33,
            'military_unit4' => 44,
            'military_spies' => 55,
            'military_assassins' => 66,
            'military_wizards' => 77,
            'military_archmages' => 88,
            'spy_strength' => 87.456,
            'wizard_strength' => 100,
            'stat_total_platinum_spent_construction' => 1000,
            'stat_total_platinum_spent_exploration' => 200,
            'stat_total_platinum_spent_investment' => 30,
            'stat_total_platinum_spent_rezoning' => 4,
            'stat_total_platinum_spent_training' => 50000,
            'stat_total_lumber_spent_construction' => 700,
            'stat_total_lumber_spent_investment' => 80,
            'stat_total_lumber_spent_training' => 9,
            'stat_total_mana_spent_investment' => 11,
            'stat_total_mana_spent_training' => 22,
            'stat_total_ore_spent_investment' => 33,
            'stat_total_ore_spent_training' => 44,
            'stat_total_gems_spent_investment' => 55,
            'stat_total_gems_spent_training' => 66,
            'stat_total_land_conquered' => 120,
            'stat_total_land_explored' => 340,
            'stat_total_land_lost' => 56,
            'discounted_land' => 25,
        ]);
        Dominion::whereKey($this->dominion->id)->update(['highest_land_achieved' => 789]);
        $dominion = $this->dominion->fresh();

        $queueService = app(QueueService::class);
        $queueService->queueResources('invasion', $dominion, ['military_unit2' => 100, 'military_unit4' => 5], 9);
        $queueService->queueResources('invasion', $dominion, ['military_unit2' => 7], 4);
        $queueService->queueResources('training', $dominion, ['military_unit1' => 1000], 6);

        $production = app(ProductionCalculator::class);
        $population = app(PopulationCalculator::class);
        $military = app(MilitaryCalculator::class);

        $entry = $this->getAdvisors()->json('dominions.' . $dominion->id);

        $this->assertSame([
            'platinum' => 123456,
            'food' => 54321,
            'lumber' => $dominion->resource_lumber,
            'mana' => $dominion->resource_mana,
            'ore' => $dominion->resource_ore,
            'gems' => $dominion->resource_gems,
            'tech' => $dominion->resource_tech,
            'boats' => 12.35,
        ], $entry['resources']);

        $this->assertEquals([
            'draft_rate' => 35,
            'draftees' => 1500,
            'unit1' => 11,
            'unit2' => 22,
            'unit3' => 33,
            'unit4' => 44,
            'spies' => 55,
            'assassins' => 66,
            'wizards' => 77,
            'archmages' => 88,
            'spy_strength' => 87.46,
            'wizard_strength' => 100,
            'offensive_modifier' => round(($military->getOffensivePowerMultiplier($dominion) - 1) * 100, 3),
            'defensive_modifier' => round(($military->getDefensivePowerMultiplier($dominion) - 1) * 100, 3),
            'spy_ratio' => [
                'offense' => round($military->getSpyRatio($dominion, 'offense'), 3),
                'defense' => round($military->getSpyRatio($dominion, 'defense'), 3),
            ],
            'wizard_ratio' => [
                'offense' => round($military->getWizardRatio($dominion, 'offense'), 3),
                'defense' => round($military->getWizardRatio($dominion, 'defense'), 3),
            ],
        ], $entry['military']);
        $this->assertSame(100, $entry['ops']['barracks_spy']['units']['returning']['unit2'][9]);
        $this->assertSame(1000, $entry['ops']['barracks_spy']['units']['training']['unit1'][6]);

        $this->assertSame(
            [
                'platinum_spent' => 51234,
                'platinum_spent_construction' => 1000,
                'platinum_spent_exploration' => 200,
                'platinum_spent_investment' => 30,
                'platinum_spent_rezoning' => 4,
                'platinum_spent_training' => 50000,
                'lumber_spent' => 789,
                'lumber_spent_construction' => 700,
                'lumber_spent_investment' => 80,
                'lumber_spent_training' => 9,
                'mana_spent' => 33,
                'mana_spent_investment' => 11,
                'mana_spent_training' => 22,
                'ore_spent' => 77,
                'ore_spent_investment' => 33,
                'ore_spent_training' => 44,
                'gems_spent' => 121,
                'gems_spent_investment' => 55,
                'gems_spent_training' => 66,
                'land_conquered' => 120,
                'land_explored' => 340,
                'land_lost' => 56,
                'highest_land_achieved' => 789,
            ],
            $entry['statistics']
        );

        $landTypes = ['plain', 'mountain', 'swamp', 'cavern', 'forest', 'hill', 'water'];
        $this->assertSame([...$landTypes, 'discounted_land', 'barren_land'], array_keys($entry['land']));
        foreach ($landTypes as $landType) {
            $this->assertSame($dominion->{'land_' . $landType}, $entry['land'][$landType]);
        }
        $this->assertSame(25, $entry['land']['discounted_land']);
        $this->assertSame(app(LandCalculator::class)->getTotalBarrenLand($dominion), $entry['land']['barren_land']);
        $this->assertSame(app(BuildingHelper::class)->getBuildingTypes(), array_keys($entry['buildings']));
        foreach ($entry['buildings'] as $buildingType => $amount) {
            $this->assertSame($dominion->{'building_' . $buildingType}, $amount);
        }

        $this->assertSame(['production', 'consumption', 'decay', 'net_change'], array_keys($entry['hourly']));
        $this->assertSame(
            ['platinum', 'food', 'lumber', 'mana', 'ore', 'gems', 'tech', 'boats'],
            array_keys($entry['hourly']['production'])
        );
        $this->assertSame($production->getPlatinumProduction($dominion), $entry['hourly']['production']['platinum']);
        $this->assertSame($production->getFoodNetChange($dominion), $entry['hourly']['net_change']['food']);
        $this->assertSame((int) round($production->getFoodConsumption($dominion)), $entry['hourly']['consumption']['food']);
        $this->assertSame(['food', 'lumber', 'mana'], array_keys($entry['hourly']['decay']));

        $this->assertSame([
            'total' => $population->getPopulation($dominion),
            'max' => $population->getMaxPopulation($dominion),
            'peasants' => $dominion->peasants,
            'military' => $population->getPopulationMilitary($dominion),
            'jobs' => $population->getEmploymentJobs($dominion),
            'employed' => $population->getPopulationEmployed($dominion),
        ], $entry['population']);
    }

    public function testReturningListsResourcesAndPrestigeFromInvasionByHour(): void
    {
        $queueService = app(QueueService::class);
        $queueService->queueResources('invasion', $this->dominion, ['resource_platinum' => 5000, 'prestige' => 20], 12);
        $queueService->queueResources('invasion', $this->dominion, ['resource_platinum' => 300, 'resource_tech' => 450], 4);
        $queueService->queueResources('invasion', $this->dominion, ['military_unit2' => 100, 'land_plain' => 40, 'discounted_land' => 40], 12);
        $queueService->queueResources('exploration', $this->dominion, ['land_plain' => 60], 12);

        $response = $this->getAdvisors();
        $returning = $response->json('dominions.' . $this->dominion->id . '.returning');

        $this->assertEquals([
            'platinum' => [12 => 5000, 4 => 300],
            'prestige' => [12 => 20],
            'tech' => [4 => 450],
        ], $returning);
        $this->assertSame(
            $this->dominion->fresh()->resource_platinum,
            $response->json('dominions.' . $this->dominion->id . '.resources.platinum')
        );
    }

    public function testRealmieSharingAdvisorsIsListedWithCurrentDataNotInfoOps(): void
    {
        $realmie = $this->createRealmie();
        $realmie->settings = ['realmadvisors' => [$this->dominion->id => true]];
        $realmie->save();
        InfoOp::create([
            'source_realm_id' => $this->dominion->realm_id,
            'source_dominion_id' => $this->dominion->id,
            'target_dominion_id' => $realmie->id,
            'type' => 'clear_sight',
            'data' => ['name' => 'Stale Snapshot'],
            'latest' => true,
        ]);

        $response = $this->getAdvisors();

        $this->assertSame(
            [$this->dominion->id, $realmie->id],
            array_map('intval', array_keys($response->json('dominions')))
        );
        $this->assertSame($realmie->name, $response->json('dominions.' . $realmie->id . '.ops.clear_sight.name'));
        $this->assertNotNull($response->json('dominions.' . $realmie->id . '.ops.barracks_spy'));
        $this->assertNotNull($response->json('dominions.' . $realmie->id . '.resources'));
    }

    public function testOwnDominionIsListedFirst(): void
    {
        $newcomer = $this->createRealmie();
        $newcomer->update(['api_key' => 'newcomer-key']);
        $this->dominion->settings = ['realmadvisors' => [$newcomer->id => true]];
        $this->dominion->save();

        $response = $this->withHeader('X-API-Key', 'newcomer-key')
            ->getJson('/api/v1/dominions/me/advisors')
            ->assertOk();

        $this->assertSame(
            [$newcomer->id, $this->dominion->id],
            array_map('intval', array_keys($response->json('dominions')))
        );
    }

    public function testRealmieNotSharingAdvisorsIsOmitted(): void
    {
        $realmie = $this->createRealmie();
        $realmie->settings = ['realmadvisors' => [$this->dominion->id => false]];
        $realmie->save();

        $this->assertArrayNotHasKey((string) $realmie->id, $this->getAdvisors()->json('dominions'));
    }

    public function testDominionsInOtherRealmsAreOmitted(): void
    {
        $otherRealm = Realm::create([
            'round_id' => $this->round->id,
            'alignment' => 'good',
            'number' => 99,
            'name' => 'Other Realm',
        ]);
        $outsider = $this->createDominion($this->createUser(), $this->round, $this->dominion->race, $otherRealm);
        $outsider->settings = ['realmadvisors' => [$this->dominion->id => true]];
        $outsider->save();

        $this->assertSame(
            [$this->dominion->id],
            array_map('intval', array_keys($this->getAdvisors()->json('dominions')))
        );
    }

    public function testLateStarterCannotSeeRealmieAdvisorsByDefault(): void
    {
        $realmie = $this->createRealmie();
        $this->dominion->created_at = $this->round->realmAssignmentDate()->addHour();
        $this->dominion->save();

        $this->assertArrayNotHasKey((string) $realmie->id, $this->getAdvisors()->json('dominions'));
    }

    public function testLateStarterCanSeeRealmieAdvisorsWhenExplicitlyShared(): void
    {
        $realmie = $this->createRealmie();
        $realmie->settings = ['realmadvisors' => [$this->dominion->id => true]];
        $realmie->save();
        $this->dominion->created_at = $this->round->realmAssignmentDate()->addHour();
        $this->dominion->save();

        $this->getAdvisors()->assertJsonPath('dominions.' . $realmie->id . '.id', $realmie->id);
    }

    public function testAvailableWhileInProtection(): void
    {
        $this->dominion->update(['protection_finished' => false]);

        $this->getAdvisors()->assertJsonPath('dominions.' . $this->dominion->id . '.id', $this->dominion->id);
    }

    public function testAvailableBeforeRoundStarts(): void
    {
        $this->round->update(['start_date' => now()->addDays(2)]);

        $this->getAdvisors()->assertJsonPath('dominions.' . $this->dominion->id . '.id', $this->dominion->id);
    }

    private function createRealmie(): Dominion
    {
        return $this->createDominion($this->createUser(), $this->round, $this->dominion->race, $this->dominion->realm);
    }

    private function getAdvisors(): TestResponse
    {
        return $this->withHeader('X-API-Key', 'advisors-key')
            ->getJson('/api/v1/dominions/me/advisors')
            ->assertOk();
    }
}
