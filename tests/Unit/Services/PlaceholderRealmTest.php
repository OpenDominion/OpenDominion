<?php

namespace OpenDominion\Tests\Unit\Services;

use Illuminate\Support\Collection;
use OpenDominion\Models\User;
use OpenDominion\Services\PlaceholderRealm;
use OpenDominion\Services\Player;
use OpenDominion\Services\RealmAssignmentService;
use PHPUnit\Framework\TestCase;

class PlaceholderRealmTest extends TestCase
{
    /**
     * Player, PlaceholderPack and PlaceholderRealm are all declared inside
     * RealmAssignmentService.php, so PSR-4 cannot resolve them by name and the
     * `use` statements above are not enough to load them. Referencing the one
     * class that does match its filename pulls the whole file in.
     */
    protected function setUp(): void
    {
        parent::setUp();

        class_exists(RealmAssignmentService::class);
    }

    public function testPlayerFactoryMarksMissingAndZeroAffinitiesUnknown(): void
    {
        $missingAffinitiesUser = new User();
        $missingAffinitiesUser->rating = 0;
        $missingAffinitiesUser->affinities = null;

        $zeroAffinitiesUser = new User();
        $zeroAffinitiesUser->rating = 1000;
        $zeroAffinitiesUser->affinities = [
            'attacker' => 0,
            'converter' => 0,
            'explorer' => 0,
            'ops' => 0,
        ];

        $missingAffinitiesPlayer = Player::fromUser($missingAffinitiesUser, ['id' => 'missing']);
        $zeroAffinitiesPlayer = Player::fromUser($zeroAffinitiesUser, ['id' => 'zero']);

        $this->assertFalse($missingAffinitiesPlayer->hasKnownAffinities);
        $this->assertFalse($zeroAffinitiesPlayer->hasKnownAffinities);
    }

    public function testCompositionExcludesUnknownPlayersFromSumsAndDenominators(): void
    {
        $realm = new PlaceholderRealm('test', collect([
            $this->knownPlayer('known-one', [40, 20, 60, 10]),
            $this->knownPlayer('known-two', [60, 40, 40, 50]),
            $this->unknownPlayer('unknown', [100, 100, 100, 100]),
        ]));

        $this->assertSame([
            'attackerAffinity' => 50.0,
            'converterAffinity' => 30.0,
            'explorerAffinity' => 50.0,
            'opsAffinity' => 30.0,
        ], $realm->getPlaystyleComposition());
    }

    public function testSpecialistAndGeneralistRealmsAreScoredDifferently(): void
    {
        // Both realms average to an identical composition, so scoring on average
        // affinity cannot tell them apart -- but one has a dedicated ops player
        // and a dedicated converter, and the other has nobody who does either.
        $specialists = new PlaceholderRealm('specialists', collect([
            $this->knownPlayer('s1', [100, 0, 100, 0]),
            $this->knownPlayer('s2', [100, 0, 100, 0]),
            $this->knownPlayer('s3', [0, 100, 0, 0]),
            $this->knownPlayer('s4', [0, 20, 0, 100]),
        ]));
        $generalists = new PlaceholderRealm('generalists', collect([
            $this->knownPlayer('g1', [50, 30, 50, 25]),
            $this->knownPlayer('g2', [50, 30, 50, 25]),
            $this->knownPlayer('g3', [50, 30, 50, 25]),
            $this->knownPlayer('g4', [50, 30, 50, 25]),
        ]));

        $this->assertSame(
            $specialists->getPlaystyleComposition(),
            $generalists->getPlaystyleComposition(),
            'the two realms must be indistinguishable by average affinity'
        );

        $opsPlayer = $this->knownPlayer('ops', [20, 20, 20, 90]);

        // The realm with no ops player wants one; the realm that already has one
        // does not. The average-based score returned exactly 0 for both.
        $this->assertGreaterThan(
            $specialists->calculatePlaystyleScore(collect([$opsPlayer])),
            $generalists->calculatePlaystyleScore(collect([$opsPlayer]))
        );
    }

    public function testScarcePlaystyleIsSpreadRatherThanStacked(): void
    {
        // Ops target is 30% of realm size, so both realms sit below it. Absolute
        // deviation improves by exactly 0.7 for either placement and cannot pick
        // between them; squared deviation prefers the realm with fewer.
        $fewer = new PlaceholderRealm('fewer', $this->realmWithOpsSpecialists(1));
        $more = new PlaceholderRealm('more', $this->realmWithOpsSpecialists(2));

        $opsPlayer = $this->knownPlayer('ops', [20, 20, 20, 90]);

        $this->assertGreaterThan(
            $more->calculatePlaystyleScore(collect([$opsPlayer])),
            $fewer->calculatePlaystyleScore(collect([$opsPlayer]))
        );
    }

    public function testSpecialistCountsTreatPlaystylesAsIndependent(): void
    {
        $realm = new PlaceholderRealm('test', collect([
            $this->knownPlayer('both', [80, 10, 10, 80]),
            $this->knownPlayer('neither', [49, 49, 49, 49]),
        ]));

        $this->assertSame([
            'attackerAffinity' => 1,
            'converterAffinity' => 0,
            'explorerAffinity' => 0,
            'opsAffinity' => 1,
        ], $realm->getSpecialistCounts($realm->players));
    }

    /**
     * Ten players identical but for ops, so only the ops counts differ.
     */
    private function realmWithOpsSpecialists(int $count): Collection
    {
        return collect(range(1, 10))->map(function (int $id) use ($count): Player {
            return $this->knownPlayer("p{$id}", [60, 20, 60, $id <= $count ? 90 : 10]);
        });
    }

    public function testUnknownIncomingPlayersDoNotChangeIdealOrNonIdealRealmScores(): void
    {
        $unknownPlayer = $this->unknownPlayer('unknown', [100, 100, 100, 100]);
        $idealRealm = new PlaceholderRealm('ideal', collect([
            $this->knownPlayer('ideal-player', [50, 30, 50, 30]),
        ]));
        $nonIdealRealm = new PlaceholderRealm('non-ideal', collect([
            $this->knownPlayer('non-ideal-player', [10, 10, 90, 90]),
        ]));

        $this->assertSame(0.0, $idealRealm->calculatePlaystyleScore(collect([$unknownPlayer])));
        $this->assertSame(0.0, $nonIdealRealm->calculatePlaystyleScore(collect([$unknownPlayer])));
    }

    public function testUnknownPackMembersHaveZeroWeightInProjectedComposition(): void
    {
        $realm = new PlaceholderRealm('test', collect([
            $this->knownPlayer('existing', [20, 30, 50, 30]),
        ]));
        $knownIncomingPlayer = $this->knownPlayer('known-incoming', [100, 30, 50, 30]);
        $unknownIncomingPlayer = $this->unknownPlayer('unknown-incoming', [100, 100, 100, 100]);

        $knownOnlyScore = $realm->calculatePlaystyleScore(collect([$knownIncomingPlayer]));
        $mixedPackScore = $realm->calculatePlaystyleScore(collect([
            $knownIncomingPlayer,
            $unknownIncomingPlayer,
        ]));

        $this->assertEqualsWithDelta($knownOnlyScore, $mixedPackScore, 0.0001);
    }

    public function testAssignmentStatsExcludeUnknownPlayersFromAggregatePlaystyleDenominator(): void
    {
        $service = new RealmAssignmentService();
        $service->realms = collect([
            new PlaceholderRealm('one', collect([
                $this->knownPlayer('known-one', [40, 20, 60, 10]),
                $this->unknownPlayer('unknown', [100, 100, 100, 100]),
            ])),
            new PlaceholderRealm('two', collect([
                $this->knownPlayer('known-two', [60, 40, 40, 50]),
            ])),
        ]);

        $this->assertSame([
            'attacker' => 50.0,
            'converter' => 30.0,
            'explorer' => 50.0,
            'ops' => 30.0,
        ], $service->getAssignmentStats()['overall_playstyle_distribution']);
    }

    /**
     * @param array{0: int|float, 1: int|float, 2: int|float, 3: int|float} $affinities
     */
    private function knownPlayer(string $id, array $affinities): Player
    {
        return new Player([
            'id' => $id,
            'rating' => 1500,
            'packId' => null,
            'attackerAffinity' => $affinities[0],
            'converterAffinity' => $affinities[1],
            'explorerAffinity' => $affinities[2],
            'opsAffinity' => $affinities[3],
        ]);
    }

    /**
     * @param array{0: int|float, 1: int|float, 2: int|float, 3: int|float} $affinities
     */
    private function unknownPlayer(string $id, array $affinities): Player
    {
        return new Player([
            'id' => $id,
            'rating' => 1000,
            'packId' => null,
            'hasKnownAffinities' => false,
            'attackerAffinity' => $affinities[0],
            'converterAffinity' => $affinities[1],
            'explorerAffinity' => $affinities[2],
            'opsAffinity' => $affinities[3],
        ]);
    }
}
