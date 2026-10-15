<?php

namespace OpenDominion\Tests\Unit\Factories;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Factories\DominionFactory;
use OpenDominion\Factories\RealmFactory;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use OpenDominion\Services\PackService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class DominionFactoryTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var User */
    protected $user;

    /** @var Round */
    protected $round;

    /** @var Race */
    protected $race;

    /** @var Realm */
    protected $realm;

    /** @var DominionFactory */
    protected $dominionFactory;

    /** @var PackService */
    protected $packService;

    /** @var RealmFactory */
    protected $realmFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
        $this->round = $this->createRound();
        $this->race = Race::firstOrFail();
        $this->realm = $this->createRealm($this->round, $this->race->alignment);

        $this->dominionFactory = $this->app->make(DominionFactory::class);
        $this->packService =  $this->app->make(PackService::class);
        $this->realmFactory = $this->app->make(RealmFactory::class);
    }

    public function testCreate()
    {
        $this->assertEquals(0, $this->round->dominions()->count());

        $dominion = $this->dominionFactory->create(
            $this->user,
            $this->realm,
            $this->race,
            'Ruler Name',
            'Dominion Name'
        );

        $this->assertEquals(1, $this->round->dominions()->count());
        $this->assertEquals($dominion->id, $this->round->dominions()->first()->id);
    }

    public function testCreateUsesEffectiveRatingsForPreStartRealmAverage(): void
    {
        $this->round->start_date = now()->addDay();
        $this->round->save();
        $this->realm->unsetRelation('round');

        $this->user->rating = 0;
        $this->user->save();

        $this->dominionFactory->create(
            $this->user,
            $this->realm,
            $this->race,
            'Unrated Ruler',
            'Unrated Dominion'
        );

        $ratedUser = $this->createUser(null, ['rating' => 1800]);
        $this->dominionFactory->create(
            $ratedUser,
            $this->realm,
            $this->race,
            'Rated Ruler',
            'Rated Dominion'
        );

        $this->assertSame(1400, $this->realm->fresh()->rating);
    }

    public function testStartingResources()
    {
        $dominion = $this->dominionFactory->create(
            $this->user,
            $this->realm,
            $this->race,
            'Ruler Name',
            'Dominion Name',
            'quick',
        );

        $expected_attributes = [
            'peasants' => 1000,
            'resource_platinum' => 50000,
            'resource_food' => 15000,
            'resource_lumber' => 15000,
            'resource_ore' => 0,
            'resource_mana' => 0,
            'military_unit2' => 0,
            'military_draftees' => 150,
        ];

        foreach ($expected_attributes as $key => $value) {
            $this->assertEquals($dominion->{$key}, $value);
        }
    }

    public function testLateStartingResources()
    {
        $this->realm->round->start_date = now()->subDays(2);
        $this->realm->round->save();

        $dominion = $this->dominionFactory->create(
            $this->user,
            $this->realm,
            $this->race,
            'Ruler Name',
            'Dominion Name',
            'quick',
        );

        $expected_attributes = [
            'peasants' => 1000,
            'resource_platinum' => 70000,
            'resource_food' => 18000,
            'resource_lumber' => 20000,
            'resource_ore' => 5000,
            'resource_mana' => 2000,
            'military_draftees' => 390,
        ];

        foreach ($expected_attributes as $key => $value) {
            $this->assertEquals($dominion->{$key}, $value);
        }
    }

    public function testAdvancedStartingResources()
    {
        $dominion = $this->dominionFactory->create(
            $this->user,
            $this->realm,
            $this->race,
            'Ruler Name',
            'Dominion Name',
            'advanced',
        );

        $expected_attributes = [
            'peasants' => 1000,
            'resource_platinum' => 120000,
            'resource_food' => 15000,
            'resource_lumber' => 15000,
            'resource_ore' => 0,
            'resource_mana' => 0,
            'military_unit2' => 0,
            'military_draftees' => 300,
        ];

        foreach ($expected_attributes as $key => $value) {
            $this->assertEquals($dominion->{$key}, $value);
        }
    }

    /**
     * @return array<string, array{0: int, 1: int, 2: bool|null}>
     */
    public static function nonPlayerIncomingDefenseProvider(): array
    {
        return [
            'mixed units, random batch types' => [900, 400, null],
            'mixed units, elite batches first' => [900, 400, true],
            'specs only' => [1500, 0, null],
            'elites only' => [0, 700, true],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('nonPlayerIncomingDefenseProvider')]
    public function testNonPlayerIncomingDefenseScheduleSplitsRoughlyEvenlyAroundHourTen(int $incSpecs, int $incElites, ?bool $randomChance): void
    {
        global $mockRandomChance;
        $mockRandomChance = $randomChance;

        try {
            for ($i = 0; $i < 200; $i++) {
                $schedule = $this->dominionFactory->getNonPlayerIncomingDefenseSchedule($incSpecs, $incElites);

                $hours = array_keys($schedule);
                $this->assertGreaterThanOrEqual(2, count($hours));
                $this->assertLessThanOrEqual(5, count($hours));
                $this->assertGreaterThanOrEqual(5, min($hours));
                $this->assertLessThanOrEqual(10, min($hours));
                $this->assertGreaterThanOrEqual(11, max($hours));
                $this->assertLessThanOrEqual(13, max($hours));

                $totals = ['military_unit2' => 0, 'military_unit3' => 0];
                $lateTotals = ['military_unit2' => 0, 'military_unit3' => 0];
                foreach ($schedule as $hour => $resources) {
                    foreach ($resources as $unitType => $amount) {
                        $this->assertGreaterThanOrEqual(0, $amount);
                        $totals[$unitType] += $amount;
                        if ($hour > 10) {
                            $lateTotals[$unitType] += $amount;
                        }
                    }
                }

                $this->assertEquals($incSpecs, $totals['military_unit2']);
                $this->assertEquals($incElites, $totals['military_unit3']);
                $this->assertGreaterThanOrEqual(floor($incSpecs * 0.4), $lateTotals['military_unit2']);
                $this->assertLessThanOrEqual(ceil($incSpecs * 0.6), $lateTotals['military_unit2']);
                $this->assertGreaterThanOrEqual(floor($incElites * 0.4), $lateTotals['military_unit3']);
                $this->assertLessThanOrEqual(ceil($incElites * 0.6), $lateTotals['military_unit3']);

                if ($incSpecs > 0 && $incElites > 0) {
                    $this->assertEqualsWithDelta(
                        $lateTotals['military_unit2'] / $incSpecs,
                        $lateTotals['military_unit3'] / $incElites,
                        0.01
                    );
                }
            }
        } finally {
            $mockRandomChance = false;
        }
    }
}
