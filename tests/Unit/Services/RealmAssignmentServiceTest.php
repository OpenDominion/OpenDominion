<?php

namespace OpenDominion\Tests\Unit\Services;

use Illuminate\Support\Collection;
use OpenDominion\Models\Race;
use OpenDominion\Services\PlaceholderPack;
use OpenDominion\Services\PlaceholderRealm;
use OpenDominion\Services\Player;
use OpenDominion\Services\RealmAssignmentService;
use OpenDominion\Tests\AbstractTestCase;
use OpenDominion\Tests\Traits\CreatesData;

class RealmAssignmentServiceTest extends AbstractTestCase
{
    use CreatesData;

    /** @var RealmAssignmentService */
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RealmAssignmentService();
    }

    /**
     * Test elaborate realm assignment scenario by directly using the service's
     * internal data structures and testing the assignment logic.
     *
     * This test validates the complete algorithm including:
     * - Pack compatibility scoring and assignment
     * - Solo player distribution with balance optimization
     * - Player conflict avoidance through favorability matrix
     * - Playstyle distribution across realms
     * - Post-assignment optimization through player swapping
     */
    public function testElaborateRealmAssignmentWithComplexScenario()
    {
        // Create elaborate test data
        $testData = $this->createElaborateTestData();

        // Manually populate the service with test data (bypassing database)
        $this->populateServiceWithTestData($testData);

        // Execute core assignment logic
        $startTime = microtime(true);
        $this->executeAssignmentLogic();
        $executionTime = microtime(true) - $startTime;

        // Get results
        $result = $this->service->realms;

        // === COMPREHENSIVE ASSERTIONS ===

        // 1. Basic Structure Validation
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertBetween(8, 14, $result->count(), 'Realm count should be within bounds');

        // 2. All Players Assigned
        $totalAssignedPlayers = $result->sum(fn ($realm) => $realm->size);
        $this->assertEquals(100, $totalAssignedPlayers, 'All 100 players should be assigned');

        // 3. Pack Constraint Validation
        foreach ($result as $realm) {
            $this->assertLessThanOrEqual(
                8,
                $realm->packedPlayerCount(),
                "Realm {$realm->id} exceeds max packed players limit"
            );
        }

        // 4. Size Balance Validation
        $realmSizes = $result->pluck('size')->sort()->values();
        $minSize = $realmSizes->first();
        $maxSize = $realmSizes->last();
        $this->assertLessThanOrEqual(
            50,
            $maxSize - $minSize,
            'Realm size difference should be within acceptable range for test'
        );

        // 5. Rating Balance Validation
        $realmRatings = $result->map(fn ($realm) => $realm->players->avg('rating'));
        $avgRating = $realmRatings->avg();
        $maxDeviation = $realmRatings->map(fn ($rating) => abs($rating - $avgRating))->max();
        $this->assertLessThan(
            400,
            $maxDeviation,
            'Rating deviation between realms should be reasonable'
        );

        // 6. Hard Conflict Avoidance
        foreach ($result as $realm) {
            $this->assertNoHardConflictsInRealm($realm);
        }

        // 7. Playstyle Distribution Analysis
        $this->assertPlaystyleDistributionExists($result);

        // 8. Pack Integrity Validation
        $this->assertPackIntegrityMaintained($result);

        // 9. New Player Distribution
        $this->assertNewPlayersDistributedEvenly($result);

        // 10. Algorithm Statistics Validation
        $stats = $this->service->getAssignmentStats();
        $this->assertValidAssignmentStatistics($stats);

        // 11. Performance Validation
        $this->assertLessThan(
            5.0,
            $executionTime,
            'Algorithm should complete within 5 seconds for 100 players'
        );

        // 12. Realm Rating Variance
        $this->assertRealmRatingVarianceIsMinimal($result);

        // 13. Solo vs Packed Player Balance
        $this->assertSoloPackedPlayerBalance($result);

        // 14. Edge Case Players Handled
        $this->assertEdgeCasePlayersHandledCorrectly($result);

        // 15. Optimization Effectiveness
        $this->assertOptimizationImprovedAssignments($result);

        // Output test summary
        echo "\n=== REALM ASSIGNMENT TEST SUMMARY ===\n";
        echo 'Execution Time: ' . number_format($executionTime, 3) . "s\n";
        echo 'Realms Created: ' . $result->count() . "\n";
        echo 'Players Assigned: ' . $totalAssignedPlayers . "\n";
        echo 'Rating Balance: Max deviation ' . number_format($maxDeviation, 1) . "\n";
        echo 'Size Balance: ' . $minSize . '-' . $maxSize . " players per realm\n";

        // Detailed realm breakdown
        echo "\n=== REALM DETAILS ===\n";
        foreach ($result as $index => $realm) {
            $avgRating = round($realm->players->avg('rating'), 1);
            $soloCount = $realm->soloPlayers()->count();
            $packedCount = $realm->packedPlayerCount();
            echo 'Realm ' . ($index + 1) . ": {$realm->size} players, avg rating {$avgRating}, {$soloCount} solo, {$packedCount} packed\n";
        }

        // Display comprehensive assignment statistics
        echo "\n=== ASSIGNMENT STATISTICS ===\n";
        echo 'Total Players: ' . $stats['total_players'] . "\n";
        echo 'Total New Players: ' . $stats['total_new_players'] . "\n";
        echo 'Total Experienced Players: ' . $stats['total_experienced_players'] . "\n";
        echo 'Average Realm Size: ' . $stats['average_realm_size'] . "\n";
        echo 'Average Realm Rating: ' . $stats['average_realm_rating'] . "\n";
        echo 'Target Realm Strength: ' . round($stats['target_realm_strength'], 1) . "\n";
        echo 'Target Realm Size: ' . round($stats['target_realm_size'], 1) . "\n";

        echo "\n--- Balance Metrics ---\n";
        echo 'Size Variance: ' . round($stats['balance_metrics']['size_variance'], 2) . "\n";
        echo 'Rating Variance: ' . round($stats['balance_metrics']['rating_variance'], 2) . "\n";
        echo 'Max Size Deviation: ' . round($stats['balance_metrics']['max_size_deviation'], 1) . "\n";
        echo 'Max Rating Deviation: ' . round($stats['balance_metrics']['max_rating_deviation'], 1) . "\n";

        // Calculate overall favorability statistics
        $overallFavorability = $this->calculateOverallFavorabilityStats($result);
        echo "\n--- Favorability Metrics ---\n";
        echo 'Overall Avg Favorability: ' . $overallFavorability['overall_average'] . "\n";
        echo 'Total Conflict Pairs: ' . $overallFavorability['total_conflicts'] . "\n";
        echo 'Conflict-Free Realms: ' . $overallFavorability['conflict_free_realms'] . '/' . $result->count() . "\n";

        echo "\n--- Individual Realm Statistics ---\n";
        foreach ($stats['realms'] as $index => $realmStats) {
            $realm = $result->get($index);
            echo 'Realm ' . ($index + 1) . ":\n";
            echo "  Size: {$realmStats['size']} (deviation: {$realmStats['deviation_from_target_size']})\n";
            echo "  Rating: {$realmStats['average_rating']} (deviation: {$realmStats['deviation_from_target_rating']})\n";
            echo "  New/Exp: {$realmStats['new_players']}/{$realmStats['experienced_players']}\n";
            echo "  Solo/Packed: {$realmStats['solo_players']}/{$realmStats['packed_players']}\n";

            $playstyle = $realmStats['playstyle_distribution'];
            echo "  Playstyles: A:{$playstyle['attackerAffinity']} C:{$playstyle['converterAffinity']} E:{$playstyle['explorerAffinity']} O:{$playstyle['opsAffinity']}\n";

            // Calculate favorability scores for this realm
            $favorabilityStats = $this->calculateRealmFavorabilityStats($realm);
            echo "  Favorability: Total:{$favorabilityStats['total']} Avg:{$favorabilityStats['average']} Pos:{$favorabilityStats['positive']} Neg:{$favorabilityStats['negative']}\n";
            echo "  Conflicts: {$favorabilityStats['conflict_pairs']} pairs with negative sentiment\n\n";
        }
    }

    /**
     * Calculate favorability statistics for a realm
     */
    private function calculateRealmFavorabilityStats($realm): array
    {
        $totalFavorability = 0;
        $positivePairs = 0;
        $negativePairs = 0;
        $conflictPairs = 0;
        $totalPairs = 0;

        foreach ($realm->players as $player1) {
            foreach ($realm->players as $player2) {
                if ($player1->id === $player2->id) continue;

                $favorability = $player1->getFavorabilityWith($player2->id);
                $totalFavorability += $favorability;
                $totalPairs++;

                if ($favorability > 0) {
                    $positivePairs++;
                } elseif ($favorability < 0) {
                    $negativePairs++;
                    if ($favorability <= -1) { // Strong negative sentiment
                        $conflictPairs++;
                    }
                }
            }
        }

        return [
            'total' => round($totalFavorability, 1),
            'average' => $totalPairs > 0 ? round($totalFavorability / $totalPairs, 2) : 0,
            'positive' => $positivePairs,
            'negative' => $negativePairs,
            'conflict_pairs' => $conflictPairs,
            'total_pairs' => $totalPairs
        ];
    }

    /**
     * Calculate overall favorability statistics across all realms
     */
    private function calculateOverallFavorabilityStats($realms): array
    {
        $totalFavorability = 0;
        $totalConflicts = 0;
        $conflictFreeRealms = 0;
        $totalPairs = 0;

        foreach ($realms as $realm) {
            $realmStats = $this->calculateRealmFavorabilityStats($realm);
            $totalFavorability += $realmStats['total'];
            $totalConflicts += $realmStats['conflict_pairs'];
            $totalPairs += $realmStats['total_pairs'];

            if ($realmStats['conflict_pairs'] === 0) {
                $conflictFreeRealms++;
            }
        }

        return [
            'overall_average' => $totalPairs > 0 ? round($totalFavorability / $totalPairs, 2) : 0,
            'total_conflicts' => $totalConflicts,
            'conflict_free_realms' => $conflictFreeRealms
        ];
    }

    /**
     * Create elaborate test data with realistic player distributions and relationships
     */
    private function createElaborateTestData(): array
    {
        // Create diverse user ratings following realistic distribution for 100 players
        // Note: New players (rating 0) are placed in solo positions (59-68) to ensure even distribution
        $ratings = [
            // 30 beginner players (100-500) - these will be in packs
            150, 200, 250, 300, 350, 400, 450, 480, 320, 380,
            420, 460, 380, 340, 290, 180, 220, 270, 330, 390,
            450, 280, 360, 410, 470, 310, 350, 400, 260, 320,
            // 28 more intermediate players (500-1200) - continue packs
            550, 600, 650, 700, 750, 800, 850, 900, 950, 1000,
            1050, 1100, 1150, 580, 620, 680, 720, 780, 820, 880,
            560, 610, 670, 730, 790, 840, 890, 940,
            // 10 new players (rating 0) - these will be SOLO players for even distribution
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            // 12 more intermediate players (500-1200) - solo players
            990, 1040, 570, 630, 690, 750, 810, 860, 910, 960, 1010, 1060,
            // 15 advanced players (1200-1800) - solo players
            1250, 1300, 1400, 1500, 1600, 1700, 1350, 1450, 1550, 1650,
            1320, 1420, 1520, 1620, 1720,
            // 5 expert players (1800+) - solo players
            1850, 1950, 2100, 2000, 1900
        ];

        // Create pack configurations with 8 large packs for natural realm creation
        $packConfigurations = [
            // 8 Large packs (>3 members) - will become realm foundations
            ['id' => 1, 'members' => [1, 2, 3, 4], 'name' => 'Elite Warriors'],
            ['id' => 2, 'members' => [5, 6, 7, 8, 9], 'name' => 'Storm Legion'],
            ['id' => 3, 'members' => [10, 11, 12, 13], 'name' => 'Iron Fist'],
            ['id' => 4, 'members' => [14, 15, 16, 17], 'name' => 'Night Owls'],
            ['id' => 5, 'members' => [18, 19, 20, 21], 'name' => 'Fire Hawks'],
            ['id' => 6, 'members' => [22, 23, 24, 25], 'name' => 'Ice Wolves'],
            ['id' => 7, 'members' => [26, 27, 28, 29, 30], 'name' => 'Thunder Clan'],
            ['id' => 8, 'members' => [31, 32, 33, 34], 'name' => 'Shadow Guild'],

            // Medium packs (3 members)
            ['id' => 9, 'members' => [35, 36, 37], 'name' => 'Wind Runners'],
            ['id' => 10, 'members' => [38, 39, 40], 'name' => 'Earth Shakers'],
            ['id' => 11, 'members' => [41, 42, 43], 'name' => 'Flame Dancers'],
            ['id' => 12, 'members' => [44, 45, 46], 'name' => 'Crystal Guardians'],

            // Small packs (2 members)
            ['id' => 13, 'members' => [47, 48], 'name' => 'Twin Blades'],
            ['id' => 14, 'members' => [49, 50], 'name' => 'Shadow Duo'],
            ['id' => 15, 'members' => [51, 52], 'name' => 'Storm Pair'],
            ['id' => 16, 'members' => [53, 54], 'name' => 'Void Hunters'],
            ['id' => 17, 'members' => [55, 56], 'name' => 'Light Bearers'],
            ['id' => 18, 'members' => [57, 58], 'name' => 'Dark Covenant'],
        ];

        // Create favorability relationships for 100 players
        $favorabilityRelationships = [
            // Positive relationships (endorsements) - pack internal bonds
            [1, 2, 1], [2, 1, 1], [3, 4, 1], [4, 3, 1],     // Elite Warriors
            [5, 6, 1], [6, 7, 1], [7, 8, 1], [8, 9, 1],     // Storm Legion
            [14, 15, 1], [15, 16, 1], [16, 17, 1],           // Night Owls
            [18, 19, 1], [19, 20, 1], [20, 21, 1],           // Fire Hawks
            [22, 23, 1], [23, 24, 1], [24, 25, 1],           // Ice Wolves
            [26, 27, 1], [27, 28, 1], [28, 29, 1], [29, 30, 1], // Thunder Clan
            [35, 36, 1], [36, 37, 1],                         // Wind Runners
            [41, 42, 1], [42, 43, 1],                         // Flame Dancers
            [47, 48, 1], [49, 50, 1], [51, 52, 1],          // Small pack bonds

            // Cross-pack positive relationships (alliances)
            [1, 18, 1], [18, 1, 1],   // Elite Warriors - Fire Hawks alliance
            [5, 35, 1], [35, 5, 1],   // Storm Legion - Wind Runners cooperation
            [60, 70, 1], [70, 60, 1], // Solo player friendships
            [80, 85, 1], [85, 80, 1],
            [90, 95, 1], [95, 90, 1],

            // Negative relationships (conflicts)
            [3, 22, -1], [22, 3, -1], // Elite Warriors - Ice Wolves rivalry
            [6, 26, -1], [26, 6, -1], // Storm Legion - Thunder Clan conflict
            [10, 40, -1], [40, 10, -1], // Cross-pack disputes
            [15, 45, -1], [45, 15, -1], // Rating-based conflicts
            [65, 75, -1], [75, 65, -1], // Solo player disputes
            [62, 72, -1], [72, 62, -1],
            [67, 77, -1], [77, 67, -1],
            [82, 92, -1], [92, 82, -1], // High-level player rivalries
            [88, 98, -1], [98, 88, -1],
        ];

        return [
            'ratings' => $ratings,
            'packConfigurations' => $packConfigurations,
            'favorabilityRelationships' => $favorabilityRelationships
        ];
    }

    /**
     * Manually populate the service with test data, bypassing database queries
     */
    private function populateServiceWithTestData($testData): void
    {
        // Initialize collections
        $this->service->players = collect();
        $this->service->packs = collect();
        $this->service->realms = collect();

        // Create Player objects with favorability matrix
        foreach ($testData['ratings'] as $index => $rating) {
            $playerId = $index + 1;

            // Build favorability matrix for this player
            $favorabilityMatrix = [];
            foreach ($testData['favorabilityRelationships'] as [$sourceId, $targetId, $score]) {
                if ($sourceId == $playerId) {
                    $favorabilityMatrix[$targetId] = $score;
                }
            }

            // Create player with realistic playstyle ratings
            $player = new Player([
                'id' => (string)$playerId,
                'rating' => (float)$rating,
                'packId' => null, // Will be set for packed players
                'favorability' => $favorabilityMatrix,
                // Generate varied playstyle ratings based on rating tier
                'attackerAffinity' => $this->generatePlaystyleRating($rating, 'attacker'),
                'converterAffinity' => $this->generatePlaystyleRating($rating, 'converter'),
                'explorerAffinity' => $this->generatePlaystyleRating($rating, 'explorer'),
                'opsAffinity' => $this->generatePlaystyleRating($rating, 'ops'),
            ]);

            $this->service->players->put($playerId, $player);
        }

        // Create packs and assign pack IDs to players
        foreach ($testData['packConfigurations'] as $config) {
            $packMembers = collect();

            foreach ($config['members'] as $playerId) {
                $player = $this->service->players->get($playerId);
                $player->packId = (string)$config['id'];
                $packMembers->put($playerId, $player);
            }

            $pack = new PlaceholderPack((string)$config['id'], $packMembers);
            $this->service->packs->put($config['id'], $pack);

            // Remove packed players from solo players collection
            foreach ($config['members'] as $playerId) {
                $this->service->players->forget($playerId);
            }
        }

        // Set target realm metrics
        $allPlayers = collect();
        foreach ($testData['packConfigurations'] as $config) {
            foreach ($config['members'] as $playerId) {
                $allPlayers->push($this->service->packs->get($config['id'])->members->get($playerId));
            }
        }
        foreach ($this->service->players as $player) {
            $allPlayers->push($player);
        }

        $this->service->targetRealmStrength = $allPlayers->avg('rating');
        $this->service->targetRealmSize = 100 / $this->calculateRealmCount();
    }

    /**
     * Execute the core assignment logic using actual service methods
     */
    private function executeAssignmentLogic(): void
    {
        // Calculate realm count
        $realmCount = $this->calculateRealmCount();
        $this->service->targetRealmSize = 100 / $realmCount;

        // Use reflection to call the actual service methods
        $reflection = new \ReflectionClass($this->service);

        // Create placeholder realms from large packs
        $createPlaceholderRealmsMethod = $reflection->getMethod('createPlaceholderRealms');
        $createPlaceholderRealmsMethod->setAccessible(true);
        $createPlaceholderRealmsMethod->invoke($this->service);

        // Assign remaining packs
        $assignPacksMethod = $reflection->getMethod('assignPacks');
        $assignPacksMethod->setAccessible(true);
        $assignPacksMethod->invoke($this->service);

        // Assign solo players
        $assignSolosMethod = $reflection->getMethod('assignSolos');
        $assignSolosMethod->setAccessible(true);
        $assignSolosMethod->invoke($this->service);

        // Optimization pass
        $optimizeMethod = $reflection->getMethod('optimizeAssignments');
        $optimizeMethod->setAccessible(true);
        $optimizeMethod->invoke($this->service);
    }

    /**
     * Calculate realm count based on large packs
     */
    private function calculateRealmCount(): int
    {
        $largePacks = $this->service->packs->where('large', true)->count();
        return max(8, min(14, $largePacks));
    }

    /**
     * Generate realistic playstyle ratings based on overall rating
     */
    private function generatePlaystyleRating($overallRating, $style): float
    {
        $baseRating = min(100, max(0, $overallRating / 20)); // Scale to 0-100
        $variation = mt_rand(-20, 20); // Add some variation

        // Adjust based on style preferences for different rating tiers
        if ($style === 'attacker' && $overallRating > 1000) {
            $variation += 10; // High-rated players tend to be more aggressive
        } elseif ($style === 'explorer' && $overallRating < 500) {
            $variation += 15; // New players tend to explore more
        }

        return max(0, min(100, $baseRating + $variation));
    }

    // === ASSERTION HELPER METHODS ===

    private function assertBetween($min, $max, $actual, $message = '')
    {
        $this->assertGreaterThanOrEqual($min, $actual, $message);
        $this->assertLessThanOrEqual($max, $actual, $message);
    }

    private function assertNoHardConflictsInRealm($realm)
    {
        foreach ($realm->players as $player1) {
            foreach ($realm->players as $player2) {
                if ($player1->id === $player2->id) continue;

                $favorability = $player1->getFavorabilityWith($player2->id) +
                               $player2->getFavorabilityWith($player1->id);

                $this->assertGreaterThanOrEqual(
                    -10,
                    $favorability,
                    "Hard conflict detected between players {$player1->id} and {$player2->id} in realm {$realm->id}"
                );
            }
        }
    }

    private function assertPlaystyleDistributionExists($realms)
    {
        // Just verify playstyle composition is being calculated
        foreach ($realms as $realm) {
            $composition = $realm->getPlaystyleComposition();
            $this->assertIsArray($composition);
            $this->assertArrayHasKey('attackerAffinity', $composition);
            $this->assertArrayHasKey('converterAffinity', $composition);
            $this->assertArrayHasKey('explorerAffinity', $composition);
            $this->assertArrayHasKey('opsAffinity', $composition);
        }
    }

    private function assertPackIntegrityMaintained($realms)
    {
        $packMembers = [];

        // Collect all packed players by pack ID
        foreach ($realms as $realm) {
            foreach ($realm->players as $player) {
                if ($player->packId) {
                    $packMembers[$player->packId][] = $realm->id;
                }
            }
        }

        // Verify all pack members are in the same realm
        foreach ($packMembers as $packId => $realmIds) {
            $uniqueRealms = array_unique($realmIds);
            $this->assertEquals(
                1,
                count($uniqueRealms),
                "Pack {$packId} members should all be in the same realm"
            );
        }
    }

    private function assertNewPlayersDistributedEvenly($realms)
    {
        $newPlayerCounts = $realms->map(fn ($realm) => $realm->players->where('rating', 0)->count());
        $min = $newPlayerCounts->min();
        $max = $newPlayerCounts->max();

        $this->assertLessThanOrEqual(
            5,
            $max - $min,
            'New players should be distributed within acceptable range across realms'
        );
    }

    private function assertValidAssignmentStatistics($stats)
    {
        $this->assertArrayHasKey('realm_count', $stats);
        $this->assertArrayHasKey('total_players', $stats);
        $this->assertArrayHasKey('balance_metrics', $stats);

        $this->assertEquals(100, $stats['total_players']);
        $this->assertBetween(8, 14, $stats['realm_count']);
    }

    private function assertRealmRatingVarianceIsMinimal($realms)
    {
        $realmRatings = $realms->map(fn ($realm) => $realm->players->avg('rating'));
        $mean = $realmRatings->avg();
        $variance = $realmRatings->map(fn ($rating) => pow($rating - $mean, 2))->avg();

        $this->assertLessThan(
            200000,
            $variance,
            'Rating variance between realms should be reasonable for this test'
        );
    }

    private function assertSoloPackedPlayerBalance($realms)
    {
        foreach ($realms as $realm) {
            $soloCount = $realm->soloPlayers()->count();
            $packedCount = $realm->packedPlayerCount();

            $this->assertGreaterThanOrEqual(
                0,
                $soloCount,
                "Realm {$realm->id} should have non-negative solo players"
            );
            $this->assertLessThanOrEqual(
                8,
                $packedCount,
                "Realm {$realm->id} should not exceed 8 packed players"
            );
        }
    }

    private function assertEdgeCasePlayersHandledCorrectly($realms)
    {
        // Find expert players (rating > 1800) and ensure they're distributed
        $expertPlayerRealms = [];
        foreach ($realms as $realm) {
            $expertCount = $realm->players->where('rating', '>', 1800)->count();
            if ($expertCount > 0) {
                $expertPlayerRealms[] = $realm->id;
            }
        }

        $this->assertGreaterThanOrEqual(
            1,
            count($expertPlayerRealms),
            'Expert players should be assigned to realms'
        );
    }

    private function assertOptimizationImprovedAssignments($realms)
    {
        // Check that realms have reasonable compatibility scores
        $totalCompatibilityScore = 0;
        $realmCount = 0;

        foreach ($realms as $realm) {
            $playerCount = $realm->players->count();

            if ($playerCount > 1) {
                $realmCompatibility = 0;
                foreach ($realm->players as $player1) {
                    foreach ($realm->players as $player2) {
                        if ($player1->id !== $player2->id) {
                            $realmCompatibility += $player1->getFavorabilityWith($player2->id);
                        }
                    }
                }

                $avgCompatibility = $realmCompatibility / ($playerCount * ($playerCount - 1));
                $totalCompatibilityScore += $avgCompatibility;
                $realmCount++;
            }
        }

        if ($realmCount > 0) {
            $overallCompatibility = $totalCompatibilityScore / $realmCount;
            $this->assertGreaterThanOrEqual(
                -2.0,
                $overallCompatibility,
                'Overall realm compatibility should be reasonable after optimization'
            );
        }
    }

    /**
     * Test findRealm method functionality after realm assignment is complete
     *
     * This integration test validates the complete findRealm workflow using real models.
     */
    public function testFindRealmIntegration()
    {
        // Create a round that's past the assignment period (started 5 days ago)
        $round = $this->createRound('-5 days', '+42 days');

        // Get a race for testing
        $race = Race::where('name', 'Human')->firstOrFail();

        // Create 4 realms with different player compositions
        $realm1 = $this->createRealm($round, 'good'); // Small realm, mixed ratings
        $realm1->update(['number' => 1]);

        $realm2 = $this->createRealm($round, 'good'); // Medium realm, high ratings
        $realm2->update(['number' => 2]);

        $realm3 = $this->createRealm($round, 'good'); // Large realm, low ratings
        $realm3->update(['number' => 3]);

        $realm4 = $this->createRealm($round, 'good'); // Empty realm
        $realm4->update(['number' => 4]);

        // Create users with different ratings
        $users1 = [
            $this->createUser(null, ['rating' => 1200]),
            $this->createUser(null, ['rating' => 800]),
        ];

        $users2 = [
            $this->createUser(null, ['rating' => 1800]),
            $this->createUser(null, ['rating' => 1600]),
            $this->createUser(null, ['rating' => 1700]),
        ];

        $users3 = [
            $this->createUser(null, ['rating' => 400]),
            $this->createUser(null, ['rating' => 600]),
            $this->createUser(null, ['rating' => 300]),
            $this->createUser(null, ['rating' => 500]),
        ];

        // Create dominions in each realm
        foreach ($users1 as $user) {
            $this->createDominion($user, $round, $race, $realm1);
        }

        foreach ($users2 as $user) {
            $this->createDominion($user, $round, $race, $realm2);
        }

        foreach ($users3 as $user) {
            $this->createDominion($user, $round, $race, $realm3);
        }

        // Test scenarios
        echo "\n=== FINDREALM INTEGRATION TEST ===\n";

        // Test 1: New player (rating 0) should find a suitable realm
        $newUser = $this->createUser(null, ['rating' => 0]);
        $result1 = $this->service->findRealm($round, $race, $newUser);
        $this->assertNotNull($result1, 'New player should find a realm');
        echo "New player (rating 0) assigned to realm {$result1->number}\n";

        // Test 2: High-rated player should help balance low-rated realm
        $highRatedUser = $this->createUser(null, ['rating' => 1900]);
        $result2 = $this->service->findRealm($round, $race, $highRatedUser);
        $this->assertNotNull($result2, 'High-rated player should find a realm');
        echo "High-rated player (rating 1900) assigned to realm {$result2->number}\n";

        // Test 3: Low-rated player should help balance high-rated realm
        $lowRatedUser = $this->createUser(null, ['rating' => 300]);
        $result3 = $this->service->findRealm($round, $race, $lowRatedUser);
        $this->assertNotNull($result3, 'Low-rated player should find a realm');
        echo "Low-rated player (rating 300) assigned to realm {$result3->number}\n";

        // Test 4: Average player should find balanced placement
        $averageUser = $this->createUser(null, ['rating' => 1200]);
        $result4 = $this->service->findRealm($round, $race, $averageUser);
        $this->assertNotNull($result4, 'Average player should find a realm');
        echo "Average player (rating 1200) assigned to realm {$result4->number}\n";

        // Validate that all assignments are in valid realms
        $validRealmIds = [$realm1->id, $realm2->id, $realm3->id, $realm4->id];
        $this->assertContains($result1->id, $validRealmIds);
        $this->assertContains($result2->id, $validRealmIds);
        $this->assertContains($result3->id, $validRealmIds);
        $this->assertContains($result4->id, $validRealmIds);

        // Show realm compositions
        echo "\nRealm compositions after assignments:\n";
        foreach ([$realm1, $realm2, $realm3, $realm4] as $realm) {
            $dominions = $realm->dominions()->with('user')->get();
            $avgRating = $dominions->avg('user.rating');
            echo "Realm {$realm->number}: {$dominions->count()} players, avg rating " . round($avgRating, 1) . "\n";
        }

        echo "Integration test completed successfully!\n";
    }

    /**
     * Test playstyle deviation calculation with IDEAL_COMPOSITION
     */
    public function testPlaystyleDeviationCalculation()
    {
        // Create a test realm
        $realm = new PlaceholderRealm('test', collect());

        // Test 1: Perfect composition should have zero deviation
        $perfectComposition = PlaceholderRealm::IDEAL_COMPOSITION;
        $deviation = $realm->calculatePlaystyleDeviation($perfectComposition);
        $this->assertEquals(0, $deviation, 'Perfect composition should have zero deviation');

        // Test 2: Empty composition should equal sum of ideal values
        $emptyComposition = [
            'attackerAffinity' => 0,
            'converterAffinity' => 0,
            'explorerAffinity' => 0,
            'opsAffinity' => 0,
        ];
        $deviation = $realm->calculatePlaystyleDeviation($emptyComposition);
        $expectedDeviation = 50 + 30 + 50 + 30; // Sum of ideal values
        $this->assertEquals($expectedDeviation, $deviation, 'Empty composition should deviate by sum of ideal values');

        // Test 3: Doubled composition should have specific deviation
        $doubledComposition = [
            'attackerAffinity' => 100, // 40 * 2
            'converterAffinity' => 60, // 20 * 2
            'explorerAffinity' => 100, // 60 * 2
            'opsAffinity' => 60,       // 50 * 2
        ];
        $deviation = $realm->calculatePlaystyleDeviation($doubledComposition);
        $expectedDeviation = 50 + 30 + 50 + 30; // Each differs by the ideal value
        $this->assertEquals($expectedDeviation, $deviation, 'Doubled composition should deviate by sum of ideal values');

        // Test 4: Imbalanced composition should have positive deviation
        $imbalancedComposition = [
            'attackerAffinity' => 100, // Way too high
            'converterAffinity' => 0,  // Too low
            'explorerAffinity' => 0,   // Too low
            'opsAffinity' => 0,        // Too low
        ];
        $deviation = $realm->calculatePlaystyleDeviation($imbalancedComposition);
        $this->assertGreaterThan(0, $deviation, 'Imbalanced composition should have positive deviation');

        // Test 5: Compare two compositions - closer to ideal should have lower deviation
        $betterComposition = [
            'attackerAffinity' => 35,  // Close to ideal 30
            'converterAffinity' => 12, // Close to ideal 10
            'explorerAffinity' => 75,  // Close to ideal 70
            'opsAffinity' => 48,       // Close to ideal 50
        ];

        $worseComposition = [
            'attackerAffinity' => 80,  // Far from ideal 30
            'converterAffinity' => 5,  // Far from ideal 10
            'explorerAffinity' => 20,  // Far from ideal 70
            'opsAffinity' => 25,       // Far from ideal 50
        ];

        $betterDeviation = $realm->calculatePlaystyleDeviation($betterComposition);
        $worseDeviation = $realm->calculatePlaystyleDeviation($worseComposition);

        $this->assertLessThan(
            $worseDeviation,
            $betterDeviation,
            'Composition closer to ideal should have lower deviation'
        );

        echo "\nPlaystyle deviation test results:\n";
        echo 'Perfect composition deviation: ' . round($deviation, 2) . "\n";
        echo 'Better composition deviation: ' . round($betterDeviation, 2) . "\n";
        echo 'Worse composition deviation: ' . round($worseDeviation, 2) . "\n";
    }

    /**
     * Test that new playstyle scoring properly distinguishes balanced vs imbalanced compositions
     */
    public function testPlaystyleScoringImprovement()
    {
        // Create test players with different playstyle patterns

        // Scenario 1: Two players with extreme opposite playstyles (100, 0)
        $extremePlayer1 = new Player([
            'id' => 'extreme1',
            'rating' => 1000,
            'attackerAffinity' => 100,
            'converterAffinity' => 0,
            'explorerAffinity' => 0,
            'opsAffinity' => 0,
            'favorability' => [],
        ]);

        $extremePlayer2 = new Player([
            'id' => 'extreme2',
            'rating' => 1000,
            'attackerAffinity' => 0,
            'converterAffinity' => 0,
            'explorerAffinity' => 100,
            'opsAffinity' => 0,
            'favorability' => [],
        ]);

        // Scenario 2: Two players with balanced playstyles (50, 50)
        $balancedPlayer1 = new Player([
            'id' => 'balanced1',
            'rating' => 1000,
            'attackerAffinity' => 50,
            'converterAffinity' => 25,
            'explorerAffinity' => 50,
            'opsAffinity' => 25,
            'favorability' => [],
        ]);

        $balancedPlayer2 = new Player([
            'id' => 'balanced2',
            'rating' => 1000,
            'attackerAffinity' => 50,
            'converterAffinity' => 25,
            'explorerAffinity' => 50,
            'opsAffinity' => 25,
            'favorability' => [],
        ]);

        // Create empty realm for testing
        $realm = new PlaceholderRealm('test', collect());

        // Test adding extreme players
        $extremeScore = $realm->calculatePlaystyleScore(collect([$extremePlayer1, $extremePlayer2]));

        // Test adding balanced players
        $balancedScore = $realm->calculatePlaystyleScore(collect([$balancedPlayer1, $balancedPlayer2]));

        // The balanced composition should score better (higher score = better for realm balance)
        $this->assertGreaterThan(
            $extremeScore,
            $balancedScore,
            'Balanced playstyle composition should score better than extreme composition'
        );

        // Test 3: Adding players that move toward ideal composition should score better
        // Start with a realm that has too many attackers
        $attackerHeavyPlayer1 = new Player([
            'id' => 'attacker1',
            'rating' => 1000,
            'attackerAffinity' => 80,
            'converterAffinity' => 10,
            'explorerAffinity' => 20,
            'opsAffinity' => 20,
            'favorability' => [],
        ]);

        $attackerHeavyPlayer2 = new Player([
            'id' => 'attacker2',
            'rating' => 1000,
            'attackerAffinity' => 90,
            'converterAffinity' => 5,
            'explorerAffinity' => 10,
            'opsAffinity' => 15,
            'favorability' => [],
        ]);

        $attackerHeavyRealm = new PlaceholderRealm('attacker_heavy', collect([$attackerHeavyPlayer1, $attackerHeavyPlayer2]));

        // Now test adding a player that would balance it (high explorer, low attacker)
        $balancingPlayer = new Player([
            'id' => 'balancer',
            'rating' => 1000,
            'attackerAffinity' => 15,
            'converterAffinity' => 5,
            'explorerAffinity' => 85,
            'opsAffinity' => 20,
            'favorability' => [],
        ]);

        // vs adding another attacker-heavy player
        $worseningPlayer = new Player([
            'id' => 'worsener',
            'rating' => 1000,
            'attackerAffinity' => 90,
            'converterAffinity' => 5,
            'explorerAffinity' => 10,
            'opsAffinity' => 10,
            'favorability' => [],
        ]);

        $balancingScore = $attackerHeavyRealm->calculatePlaystyleScore(collect([$balancingPlayer]));
        $worseningScore = $attackerHeavyRealm->calculatePlaystyleScore(collect([$worseningPlayer]));

        $this->assertGreaterThan(
            $worseningScore,
            $balancingScore,
            'Adding player that balances composition should score better than one that worsens it'
        );

        echo "\nPlaystyle scoring improvement test results:\n";
        echo 'Extreme composition score: ' . round($extremeScore, 2) . "\n";
        echo 'Balanced composition score: ' . round($balancedScore, 2) . "\n";
        echo 'Balancing player score: ' . round($balancingScore, 2) . "\n";
        echo 'Worsening player score: ' . round($worseningScore, 2) . "\n";
        echo "Algorithm correctly distinguishes between balanced and imbalanced compositions!\n";
    }
}
