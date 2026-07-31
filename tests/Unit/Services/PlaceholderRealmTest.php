<?php

namespace OpenDominion\Tests\Unit\Services;

use OpenDominion\Models\User;
use OpenDominion\Services\PlaceholderRealm;
use OpenDominion\Services\Player;
use OpenDominion\Services\RealmAssignmentService;
use PHPUnit\Framework\TestCase;

class PlaceholderRealmTest extends TestCase
{
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

    public function testProjectedCompositionUsesAWeightedAverage(): void
    {
        $existingPlayers = collect();
        foreach (range(1, 9) as $id) {
            $existingPlayers->push($this->knownPlayer("existing-{$id}", [40, 30, 50, 30]));
        }

        $realm = new PlaceholderRealm('test', $existingPlayers);
        $incomingPlayer = $this->knownPlayer('incoming', [100, 30, 50, 30]);

        $this->assertEqualsWithDelta(
            6.0,
            $realm->calculatePlaystyleScore(collect([$incomingPlayer])),
            0.0001
        );
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
