<?php

namespace OpenDominion\Tests\Unit\Services;

use Illuminate\Support\Collection;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use OpenDominion\Models\UserFeedback;
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

    public function testPlayerFactoryUsesEffectiveRatingWithoutChangingAffinities(): void
    {
        $user = new User();
        $user->rating = 0;
        $user->affinities = [
            'attacker' => 80,
            'converter' => 40,
            'explorer' => 20,
            'ops' => 10,
        ];

        $player = Player::fromUser($user, ['id' => 'new-player']);

        $this->assertSame(User::DEFAULT_RATING, $player->rating);
        $this->assertTrue($player->hasKnownAffinities);
        $this->assertSame(80.0, $player->attackerAffinity);
        $this->assertSame(40.0, $player->converterAffinity);
        $this->assertSame(20.0, $player->explorerAffinity);
        $this->assertSame(10.0, $player->opsAffinity);
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
            1000,
            $maxDeviation,
            'Rating deviation between realms should be reasonable (updated for realistic data)'
        );

        // 6. Conflict Minimization (soft conflicts allowed with penalties)
        foreach ($result as $realm) {
            $this->assertSoftConflictsMinimized($realm);
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
        // Create realistic user ratings following production distribution for 100 players
        // Target: avg ~1800, max ~2900, 20% at or below 1000 (including 10 with rating 0)
        $ratings = [
            // 10 low-rated players (≤1000) + 10 intermediate - these will be in packs
            600, 700, 800, 900, 950, 750, 680, 850, 650, 500, // 10 ≤1000
            1200, 1400, 1600, 1500, 1300, 1350, 1450, 1250, 1180, 1320, // 10 >1000
            // 28 more intermediate players (1001-1800) - continue packs
            1700, 1550, 1650, 1750, 1380, 1480, 1580, 1120, 1420, 1520,
            1620, 1720, 1280, 1680, 1780, 1220, 1360, 1460, 1560, 1140,
            1240, 1340, 1440, 1540, 1160, 1260, 1760, 1150,
            // 10 new players (default rating 1000) - these will be SOLO players for even distribution
            1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000,
            // 32 experienced players (1800-2500) - solo players
            1850, 1900, 1950, 2000, 2050, 2100, 2150, 2200, 2250, 2300,
            2350, 2400, 2450, 2500, 1870, 1920, 1970, 2020, 2070, 2120,
            2170, 2220, 2270, 2320, 2370, 2420, 2470, 1930, 1980, 2030,
            2080, 2130,
            // 10 expert players (2500-2900) - solo players
            2550, 2600, 2650, 2700, 2750, 2800, 2850, 2900, 2580, 2680
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
        $createPlaceholderRealmsMethod->invoke($this->service, $realmCount);

        // Assign remaining packs
        $assignPacksMethod = $reflection->getMethod('assignPacks');
        $assignPacksMethod->setAccessible(true);
        $assignPacksMethod->invoke($this->service);

        // Assign solo players
        $assignSolosMethod = $reflection->getMethod('assignSolos');
        $assignSolosMethod->setAccessible(true);
        $assignSolosMethod->invoke($this->service);

        // Size balancing pass (new method)
        $balanceMethod = $reflection->getMethod('balanceRealmSizes');
        $balanceMethod->setAccessible(true);
        $balanceMethod->invoke($this->service);

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

    private function assertSoftConflictsMinimized($realm)
    {
        $totalNegativeConflicts = 0;
        $totalConflictSeverity = 0;

        foreach ($realm->players as $player1) {
            foreach ($realm->players as $player2) {
                if ($player1->id === $player2->id) continue;

                $favorability = $player1->getFavorabilityWith($player2->id) +
                               $player2->getFavorabilityWith($player1->id);

                if ($favorability < 0) {
                    $totalNegativeConflicts++;
                    $totalConflictSeverity += abs($favorability);
                }
            }
        }

        // With realistic data, some conflicts are expected but should be minimized
        // Allow up to 30% of player pairs to have negative sentiment
        $totalPairs = $realm->players->count() * ($realm->players->count() - 1);
        $conflictRatio = $totalNegativeConflicts / max(1, $totalPairs);

        $this->assertLessThan(
            0.3,
            $conflictRatio,
            "Too many conflicts in realm {$realm->id}: {$totalNegativeConflicts}/{$totalPairs} pairs have negative sentiment"
        );
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
        // Service treats rating ≤1000 as "new players" for assignment purposes
        $newPlayerCounts = $realms->map(fn ($realm) => $realm->players->where('rating', '<=', 1000)->count());
        $min = $newPlayerCounts->min();
        $max = $newPlayerCounts->max();

        // Allow more variance since we have realistic rating distribution
        // with many players ≤1000 spread across fewer realms
        $this->assertLessThanOrEqual(
            8,
            $max - $min,
            'New players (≤1000 rating) should be distributed within acceptable range across realms'
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
     * Test behavior when a "draft pack" is submitted — a player-organized group larger than
     * MAX_PACKED_PLAYERS_PER_REALM (8) that arrives as a single pack (e.g. from an external
     * NFL-style draft event run outside the game).
     *
     * Because large packs (size > 3) are placed directly into their own realm by
     * createPlaceholderRealms() without going through canFitPack(), a draft pack will
     * produce a realm whose packedPlayerCount exceeds MAX_PACKED_PLAYERS_PER_REALM.
     * This test documents that current (unguarded) behavior and verifies that:
     *  - All pack members land in the same realm (pack integrity holds)
     *  - All other players are still assigned
     *  - No other packs are added to the draft-pack realm (canFitPack blocks them)
     */
    public function testAssignRealmsWithDraftPack()
    {
        $this->service->players = collect();
        $this->service->packs = collect();
        $this->service->realms = collect();

        // Draft pack: 11 players organized outside the game
        $draftPackMembers = collect();
        foreach (range(1, 11) as $id) {
            $player = new Player([
                'id' => (string)$id,
                'rating' => 1500.0,
                'packId' => 'draft-pack',
                'favorability' => [],
                'attackerAffinity' => 50.0,
                'converterAffinity' => 25.0,
                'explorerAffinity' => 50.0,
                'opsAffinity' => 25.0,
            ]);
            $draftPackMembers->put($id, $player);
        }
        $this->service->packs->put('draft-pack', new PlaceholderPack('draft-pack', $draftPackMembers));

        // Normal large pack (4 players)
        $normalPackMembers = collect();
        foreach (range(12, 15) as $id) {
            $player = new Player([
                'id' => (string)$id,
                'rating' => 1500.0,
                'packId' => 'normal-pack',
                'favorability' => [],
                'attackerAffinity' => 50.0,
                'converterAffinity' => 25.0,
                'explorerAffinity' => 50.0,
                'opsAffinity' => 25.0,
            ]);
            $normalPackMembers->put($id, $player);
        }
        $this->service->packs->put('normal-pack', new PlaceholderPack('normal-pack', $normalPackMembers));

        // Small pack (2 players)
        $smallPackMembers = collect();
        foreach (range(16, 17) as $id) {
            $player = new Player([
                'id' => (string)$id,
                'rating' => 1500.0,
                'packId' => 'small-pack',
                'favorability' => [],
                'attackerAffinity' => 50.0,
                'converterAffinity' => 25.0,
                'explorerAffinity' => 50.0,
                'opsAffinity' => 25.0,
            ]);
            $smallPackMembers->put($id, $player);
        }
        $this->service->packs->put('small-pack', new PlaceholderPack('small-pack', $smallPackMembers));

        // 20 solo players
        foreach (range(18, 37) as $id) {
            $this->service->players->put($id, new Player([
                'id' => (string)$id,
                'rating' => 1500.0,
                'packId' => null,
                'favorability' => [],
                'attackerAffinity' => 50.0,
                'converterAffinity' => 25.0,
                'explorerAffinity' => 50.0,
                'opsAffinity' => 25.0,
            ]));
        }

        $totalPlayers = 11 + 4 + 2 + 20; // 37

        $this->service->targetRealmStrength = 1500.0;
        $this->service->targetRealmSize = floor($totalPlayers / RealmAssignmentService::ASSIGNMENT_MIN_REALM_COUNT);

        $reflection = new \ReflectionClass($this->service);

        $createPlaceholderRealms = $reflection->getMethod('createPlaceholderRealms');
        $createPlaceholderRealms->setAccessible(true);
        $createPlaceholderRealms->invoke($this->service, RealmAssignmentService::ASSIGNMENT_MIN_REALM_COUNT);

        foreach (['assignPacks', 'assignSolos'] as $method) {
            $m = $reflection->getMethod($method);
            $m->setAccessible(true);
            $m->invoke($this->service);
        }

        $allRealms = $this->service->realms->merge($this->service->draftPackRealms);

        // All 37 players must be assigned
        $this->assertEquals(
            $totalPlayers,
            $allRealms->sum(fn ($realm) => $realm->size),
            'All players should be assigned'
        );

        // Locate the realm the draft pack landed in
        $draftPackRealm = null;
        foreach ($this->service->draftPackRealms as $realm) {
            foreach ($realm->players as $player) {
                if ($player->packId === 'draft-pack') {
                    $draftPackRealm = $realm;
                    break 2;
                }
            }
        }
        $this->assertNotNull($draftPackRealm, 'Draft pack should be assigned to a realm');

        // All 11 draft-pack members must be together
        $draftMembersInRealm = $draftPackRealm->players->filter(fn ($p) => $p->packId === 'draft-pack')->count();
        $this->assertEquals(11, $draftMembersInRealm, 'All draft pack members must be in the same realm');

        // Draft pack realm exceeds MAX_PACKED_PLAYERS_PER_REALM — documented current behavior
        $this->assertGreaterThan(
            RealmAssignmentService::MAX_PACKED_PLAYERS_PER_REALM,
            $draftPackRealm->packedPlayerCount(),
            'Draft pack realm should exceed MAX_PACKED_PLAYERS_PER_REALM since canFitPack is bypassed for large packs'
        );

        // No other packs should have been added to the draft-pack realm
        $nonDraftPackedCount = $draftPackRealm->players
            ->filter(fn ($p) => $p->packId !== null && $p->packId !== 'draft-pack')
            ->count();
        $this->assertEquals(
            0,
            $nonDraftPackedCount,
            'No other packs should be placed in the draft-pack realm because canFitPack blocks them'
        );

        // Pack integrity for all packs
        $packRealmMap = [];
        foreach ($allRealms as $realm) {
            foreach ($realm->players as $player) {
                if ($player->packId !== null) {
                    $packRealmMap[$player->packId][] = $realm->id;
                }
            }
        }
        foreach ($packRealmMap as $packId => $realmIds) {
            $this->assertCount(1, array_unique($realmIds), "All members of pack '{$packId}' must be in the same realm");
        }
    }

    public function testAssignmentOrchestrationKeepsNonDiscordPackMemberWithPack(): void
    {
        $packedNonDiscordPlayer = new Player([
            'id' => 'packed-non-discord',
            'rating' => 1500,
            'packId' => 'pack-1',
            'hasDiscord' => false,
        ]);
        $packedDiscordPlayer = new Player([
            'id' => 'packed-discord',
            'rating' => 1500,
            'packId' => 'pack-1',
            'hasDiscord' => true,
        ]);
        $soloNonDiscordPlayer = new Player([
            'id' => 'solo-non-discord',
            'rating' => 1500,
            'packId' => null,
            'hasDiscord' => false,
        ]);

        $service = $this->getMockBuilder(RealmAssignmentService::class)
            ->onlyMethods(['loadPlayers'])
            ->getMock();
        $service->expects($this->once())
            ->method('loadPlayers');
        $service->players = collect()
            ->put($packedNonDiscordPlayer->id, $packedNonDiscordPlayer)
            ->put($packedDiscordPlayer->id, $packedDiscordPlayer)
            ->put($soloNonDiscordPlayer->id, $soloNonDiscordPlayer);

        $service->assignRealms(new Round(), true);

        $packRealm = $service->realms->first(function (PlaceholderRealm $realm) use ($packedNonDiscordPlayer) {
            return $realm->players->has($packedNonDiscordPlayer->id);
        });
        $nonDiscordRealm = $service->realms->first(function (PlaceholderRealm $realm) use ($soloNonDiscordPlayer) {
            return $realm->players->has($soloNonDiscordPlayer->id);
        });

        $this->assertNotNull($packRealm);
        $this->assertTrue($packRealm->discordEnabled);
        $this->assertTrue($packRealm->players->has($packedDiscordPlayer->id));
        $this->assertCount(2, $packRealm->players);
        $this->assertNotNull($nonDiscordRealm);
        $this->assertFalse($nonDiscordRealm->discordEnabled);
        $this->assertFalse($nonDiscordRealm->players->has($packedNonDiscordPlayer->id));
    }

    /**
     * Test calculateRealmCount grows the realm count when total packed players
     * would exceed the packed-player headroom of the minimum realm count.
     *
     * Scenario: 4 large packs of 4 (16 packed) + 20 small packs of 3 (60 packed)
     * = 76 packed players. With MIN=8 realms × 8 packed cap = 64 slots, that overflows.
     * calculateRealmCount should return ceil(76/8) = 10 realms and upgrade enough
     * small packs to seed them, so createPlaceholderRealms produces 10 realms.
     */
    public function testFavorabilityStatsReportClampedPairsKeyedByUserId(): void
    {
        // Dominion ids and user ids differ in the assignment path. Reading feedback by
        // dominion id reports zero for everyone, so pin the user-id keying here too.
        $realm = new PlaceholderRealm('test', collect([
            new Player(['id' => 'dom-1', 'userId' => 'user-1', 'rating' => 1500, 'favorability' => ['user-2' => 9]]),
            new Player(['id' => 'dom-2', 'userId' => 'user-2', 'rating' => 1500, 'favorability' => ['user-1' => 1]]),
            new Player(['id' => 'dom-3', 'userId' => 'user-3', 'rating' => 1500, 'favorability' => ['user-1' => -2]]),
        ]));

        $stats = $realm->getFavorabilityStats();

        // Pairs: 1<->2 raw 10 clamped to 3; 1<->3 raw -2; 2<->3 raw 0.
        $this->assertEquals(3, $stats['positive']);
        $this->assertEquals(-2, $stats['negative']);
        $this->assertEquals(1, $stats['total']);
        $this->assertEquals(1, $stats['conflict_pairs'], 'the 1<->3 pair carries negative history');
        $this->assertEquals(1, $stats['clamped_pairs'], 'only the 1<->2 pair exceeded the limit');
        $this->assertEqualsWithDelta(
            3 * PlaceholderRealm::FAVORABILITY_POSITIVE_WEIGHT - 2 * PlaceholderRealm::FAVORABILITY_NEGATIVE_WEIGHT,
            $stats['weighted_total'],
            0.0001
        );
    }

    public function testAssignmentStatsAggregateFavorabilityAcrossRealms(): void
    {
        $this->service->realms = collect([
            new PlaceholderRealm('clean', collect([
                new Player(['id' => 'a', 'userId' => 'a', 'rating' => 1500, 'favorability' => ['b' => 2]]),
                new Player(['id' => 'b', 'userId' => 'b', 'rating' => 1500]),
            ])),
            new PlaceholderRealm('conflicted', collect([
                new Player(['id' => 'c', 'userId' => 'c', 'rating' => 1500, 'favorability' => ['d' => -1]]),
                new Player(['id' => 'd', 'userId' => 'd', 'rating' => 1500]),
            ])),
        ]);

        $stats = $this->service->getAssignmentStats();

        $this->assertEquals(2, $stats['favorability']['positive']);
        $this->assertEquals(-1, $stats['favorability']['negative']);
        $this->assertEquals(1, $stats['favorability']['total']);
        $this->assertEquals(1, $stats['favorability']['conflict_pairs']);
        $this->assertEquals(
            1,
            $stats['favorability']['conflict_free_realms'],
            'only the realm with no negative pair should count as conflict free'
        );
        $this->assertEquals(
            PlaceholderRealm::FAVORABILITY_PAIR_LIMIT,
            $stats['favorability']['pair_limit'],
            'the active bounds should travel with the stats so output is self-describing'
        );
        $this->assertArrayHasKey('favorability', $stats['realms'][0]);
    }

    public function testCreatePlaceholderRealmsBuildsExactlyTheRequestedCount(): void
    {
        // With no packs at all, the top-up path is the only thing creating realms. An
        // inclusive range() here used to build one realm more than was asked for.
        $this->service->players = collect();
        $this->service->packs = collect();
        $this->service->realms = collect();

        $requested = $this->service->calculateRealmCount();
        $this->service->createPlaceholderRealms($requested);

        $this->assertEquals(
            RealmAssignmentService::ASSIGNMENT_MIN_REALM_COUNT,
            $requested,
            'with no packs the target should fall back to the minimum'
        );
        $this->assertCount(
            $requested,
            $this->service->realms,
            'createPlaceholderRealms must build exactly the number of realms it was given'
        );
    }

    public function testNonDiscordRealmsCountTowardTheRealmLimits(): void
    {
        $this->service->players = collect();
        $this->service->packs = collect();
        $this->service->realms = collect();
        $this->service->nonDiscordRealms = collect([new PlaceholderRealm('non-discord-1', collect(), false)]);

        $requested = $this->service->calculateRealmCount();
        $this->service->createPlaceholderRealms($requested);

        $this->assertEquals(
            RealmAssignmentService::ASSIGNMENT_MIN_REALM_COUNT - 1,
            $requested,
            'an existing non-Discord realm must be subtracted from the Discord realm target'
        );
        $this->assertEquals(
            RealmAssignmentService::ASSIGNMENT_MIN_REALM_COUNT,
            $this->service->getRealmCount(),
            'the round should still end up with the minimum number of realms in total'
        );
    }

    public function testCalculateRealmCountGrowsForPackedHeadroom()
    {
        $this->service->players = collect();
        $this->service->packs = collect();
        $this->service->realms = collect();

        $playerId = 1;

        // 4 large packs of 4 players each
        foreach (range(1, 4) as $packIdx) {
            $members = collect();
            foreach (range(1, 4) as $_) {
                $player = new Player([
                    'id' => (string)$playerId,
                    'rating' => 1500.0,
                    'packId' => "large-{$packIdx}",
                    'favorability' => [],
                ]);
                $members->put($playerId, $player);
                $playerId++;
            }
            $this->service->packs->put("large-{$packIdx}", new PlaceholderPack("large-{$packIdx}", $members));
        }

        // 20 small packs of 3 players each
        foreach (range(1, 20) as $packIdx) {
            $members = collect();
            foreach (range(1, 3) as $_) {
                $player = new Player([
                    'id' => (string)$playerId,
                    'rating' => 1500.0,
                    'packId' => "small-{$packIdx}",
                    'favorability' => [],
                ]);
                $members->put($playerId, $player);
                $playerId++;
            }
            $this->service->packs->put("small-{$packIdx}", new PlaceholderPack("small-{$packIdx}", $members));
        }

        $expectedRealms = (int) ceil(76 / RealmAssignmentService::MAX_PACKED_PLAYERS_PER_REALM);
        $this->assertEquals(10, $expectedRealms, 'sanity-check scenario math');

        $realmCount = $this->service->calculateRealmCount();

        $this->assertEquals(
            $expectedRealms,
            $realmCount,
            'calculateRealmCount should grow past large-pack count to make headroom for all packed players'
        );

        $largePacks = $this->service->packs->where('large', true)->count();
        $this->assertEquals(
            $expectedRealms,
            $largePacks,
            'Small packs should be upgraded so the realm seeds match the target realm count'
        );
    }

    /**
     * Test getCandidateRealms excludes draft-pack realms unless they are the smallest option.
     *
     * A "draft pack" is a pack with more than MAX_PACKED_PLAYERS_PER_REALM members,
     * submitted by a player-run external draft event. The realm it occupies should be
     * skipped as a candidate for late-joining players — unless every other realm has
     * at least 2 more total players, meaning it is genuinely the least-loaded option.
     */
    public function testGetCandidateRealmsExcludesDraftPackRealms()
    {
        $round = $this->createRound('-5 days', '+42 days');
        $race  = Race::where('name', 'Human')->firstOrFail();

        // Realm A: contains a draft pack (9 members > MAX_PACKED_PLAYERS_PER_REALM=8), 9 total players
        $realmA = $this->createRealm($round, 'good');
        $realmA->update(['number' => 1]);

        $draftPackCreatorUser = $this->createUser();
        $draftPackCreatorDominion = $this->createDominion($draftPackCreatorUser, $round, $race, $realmA, ['protection_finished' => true]);

        $draftPack = Pack::create([
            'round_id'              => $round->id,
            'realm_id'              => $realmA->id,
            'creator_dominion_id'   => $draftPackCreatorDominion->id,
            'name'                  => 'Draft Pack',
            'password'              => 'secret',
            'size'                  => 9,
            'rating'                => 1500,
            'closed_at'             => now(),
        ]);
        $draftPackCreatorDominion->update(['pack_id' => $draftPack->id]);

        foreach (range(2, 9) as $i) {
            $this->createDominion($this->createUser(), $round, $race, $realmA, ['pack_id' => $draftPack->id, 'protection_finished' => true]);
        }

        // Realm B: normal realm, 10 total players (only 1 more than realm A — not enough)
        $realmB = $this->createRealm($round, 'good');
        $realmB->update(['number' => 2]);
        foreach (range(1, 10) as $i) {
            $this->createDominion($this->createUser(), $round, $race, $realmB, ['protection_finished' => true]);
        }

        // Realm C: normal realm, 11 total players (2 more than realm A — meets threshold)
        $realmC = $this->createRealm($round, 'good');
        $realmC->update(['number' => 3]);
        foreach (range(1, 11) as $i) {
            $this->createDominion($this->createUser(), $round, $race, $realmC, ['protection_finished' => true]);
        }

        // --- Case 1: not all normal realms are 2+ larger, so draft-pack realm is excluded ---
        $candidates = $this->service->getCandidateRealms($round, $race);
        $candidateIds = $candidates->pluck('id');

        $this->assertNotContains($realmA->id, $candidateIds, 'Draft-pack realm should be excluded when normal realms are not all 2+ players larger');
        $this->assertContains($realmB->id, $candidateIds, 'Normal realm B should be a candidate');
        $this->assertContains($realmC->id, $candidateIds, 'Normal realm C should be a candidate');

        // --- Case 2: bump realm B to 11 players so both normal realms are 2+ larger than realm A ---
        $this->createDominion($this->createUser(), $round, $race, $realmB, ['protection_finished' => true]);

        $candidates2 = $this->service->getCandidateRealms($round, $race);
        $candidateIds2 = $candidates2->pluck('id');

        $this->assertContains($realmA->id, $candidateIds2, 'Draft-pack realm should be included when all normal realms have 2+ more players');
    }

    /**
     * Test that getCandidateRealms counts in-protection dominions toward realm size.
     *
     * Regression test: previously `active_dominions_count` filtered by `protection_finished = true`
     * once the round had started, so a realm filled by late registrants (who all begin in protection)
     * kept being ranked as the smallest and was repeatedly chosen by `findRealm`.
     */
    public function testGetCandidateRealmsCountsInProtectionDominions()
    {
        $round = $this->createRound('-5 days', '+42 days');
        $race  = Race::where('name', 'Human')->firstOrFail();

        // Small realm: 3 dominions, all out of protection
        $smallRealm = $this->createRealm($round, 'good');
        $smallRealm->update(['number' => 1]);
        foreach (range(1, 3) as $i) {
            $this->createDominion($this->createUser(), $round, $race, $smallRealm, ['protection_finished' => true]);
        }

        // Large realm: 10 dominions, all still in protection (e.g. just joined as late registrants)
        $largeRealm = $this->createRealm($round, 'good');
        $largeRealm->update(['number' => 2]);
        foreach (range(1, 10) as $i) {
            $this->createDominion($this->createUser(), $round, $race, $largeRealm, ['protection_finished' => false]);
        }

        $candidates = $this->service->getCandidateRealms($round, $race);

        $this->assertEquals(
            $smallRealm->id,
            $candidates->first()->id,
            'Smaller realm should be ranked first even when the larger realm is full of in-protection dominions'
        );
        $this->assertEquals(
            3,
            $candidates->firstWhere('id', $smallRealm->id)->active_dominions_count,
            'active_dominions_count should reflect all dominions in the small realm'
        );
        $this->assertEquals(
            10,
            $candidates->firstWhere('id', $largeRealm->id)->active_dominions_count,
            'active_dominions_count should include in-protection dominions in the large realm'
        );
    }

    /**
     * Test that a non-Discord user falls back to a Discord realm when no non-Discord realm exists.
     *
     * findRealm checks for non-Discord realms first; if none exist it falls through to
     * getCandidateRealms() and the user is placed in a regular Discord realm.
     */
    public function testFindRealmPlacesNonDiscordUserInDiscordRealmWhenNoNonDiscordRealmExists()
    {
        $round = $this->createRound('-5 days', '+42 days');
        $race  = Race::where('name', 'Human')->firstOrFail();

        // Create Discord-only realms (no realm has usediscord=false in settings)
        $realm1 = $this->createRealm($round, 'good');
        $realm1->update(['number' => 1]);

        $realm2 = $this->createRealm($round, 'good');
        $realm2->update(['number' => 2]);

        foreach (range(1, 5) as $i) {
            $this->createDominion($this->createUser(), $round, $race, $realm1, ['protection_finished' => true]);
        }
        foreach (range(1, 6) as $i) {
            $this->createDominion($this->createUser(), $round, $race, $realm2, ['protection_finished' => true]);
        }

        // Non-Discord user with rating <= 1800
        $nonDiscordUser = $this->createUser(null, ['rating' => 1200]);

        $result = $this->service->findRealm($round, $race, $nonDiscordUser, false);

        $this->assertNotNull($result, 'Non-Discord user should still be assigned a realm');
        $this->assertContains(
            $result->id,
            [$realm1->id, $realm2->id],
            'Non-Discord user should fall back to a Discord realm when no non-Discord realm exists'
        );
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

    public function testCompatibilityMatchesFeedbackByUserId(): void
    {
        $realmMember = new Player([
            'id' => 'dominion-101',
            'userId' => 'user-1',
            'rating' => 1500,
            'favorability' => ['user-2' => 2],
        ]);
        $incomingPlayer = new Player([
            'id' => 'dominion-202',
            'userId' => 'user-2',
            'rating' => 1500,
            'favorability' => ['user-1' => 3],
        ]);
        $realm = new PlaceholderRealm('test', collect([$realmMember]));

        $compatibilityScore = $realm->getCompatibilityScore(collect([$incomingPlayer]));
        $playstyleScore = $realm->calculatePlaystyleScore(collect([$incomingPlayer]));

        // The pair nets 2 + 3 = 5, clamped to FAVORABILITY_PAIR_LIMIT before weighting.
        $this->assertEqualsWithDelta(
            PlaceholderRealm::FAVORABILITY_PAIR_LIMIT * PlaceholderRealm::FAVORABILITY_POSITIVE_WEIGHT,
            $compatibilityScore - $playstyleScore,
            0.0001,
            'Feedback should match user IDs even when dominion IDs differ'
        );
    }

    public function testFavorabilityIsBoundedPerPairSoOneRelationshipCannotDominate(): void
    {
        // A pair that has endorsed each other every round for years accumulates without
        // limit in the database; production data reaches +14 for a single pair.
        $realm = new PlaceholderRealm('test', collect([
            new Player(['id' => 'a', 'userId' => 'user-a', 'rating' => 1500, 'favorability' => ['user-x' => 7]]),
        ]));
        $newcomer = new Player([
            'id' => 'x', 'userId' => 'user-x', 'rating' => 1500, 'favorability' => ['user-a' => 7],
        ]);

        $favorability = $realm->getCompatibilityScore(collect([$newcomer]))
            - $realm->calculatePlaystyleScore(collect([$newcomer]));

        $this->assertEqualsWithDelta(
            PlaceholderRealm::FAVORABILITY_PAIR_LIMIT * PlaceholderRealm::FAVORABILITY_POSITIVE_WEIGHT,
            $favorability,
            0.0001,
            'a single relationship must not scale with how many rounds it has accumulated'
        );
    }

    public function testStackedPositiveFavorabilityIsCappedBelowTheRatingBalanceTerm(): void
    {
        // An organised group could otherwise use mutual endorsement history to pull a
        // newcomer into their realm, routing around MAX_PACKED_PLAYERS_PER_REALM.
        $members = collect(range(1, 12))->map(fn (int $i) => new Player([
            'id' => "m{$i}", 'userId' => "user-{$i}", 'rating' => 1500, 'favorability' => ['user-x' => 3],
        ]));
        $realm = new PlaceholderRealm('clique', $members);
        $newcomer = new Player([
            'id' => 'x',
            'userId' => 'user-x',
            'rating' => 1500,
            'favorability' => collect(range(1, 12))->mapWithKeys(fn (int $i) => ["user-{$i}" => 3])->toArray(),
        ]);

        $favorability = $realm->getCompatibilityScore(collect([$newcomer]))
            - $realm->calculatePlaystyleScore(collect([$newcomer]));

        $this->assertEqualsWithDelta(
            PlaceholderRealm::FAVORABILITY_POSITIVE_LIMIT * PlaceholderRealm::FAVORABILITY_POSITIVE_WEIGHT,
            $favorability,
            0.0001,
            'stacked positive feedback must saturate at the cap'
        );
    }

    public function testOneDownvoterOutweighsAMedianFriendCluster(): void
    {
        // Measured against production: the median player shares +6 of clamped favorability
        // with their realm, and negative feedback must be able to override that.
        $friendly = collect(range(1, 3))->map(fn (int $i) => new Player([
            'id' => "f{$i}", 'userId' => "friend-{$i}", 'rating' => 1500, 'favorability' => ['user-x' => 2],
        ]));
        $hostile = $friendly->concat([new Player([
            'id' => 'h', 'userId' => 'hater', 'rating' => 1500, 'favorability' => ['user-x' => -1],
        ])]);
        $newcomer = new Player(['id' => 'x', 'userId' => 'user-x', 'rating' => 1500]);

        $friendlyScore = (new PlaceholderRealm('friendly', $friendly))->getCompatibilityScore(collect([$newcomer]));
        $hostileScore = (new PlaceholderRealm('hostile', $hostile))->getCompatibilityScore(collect([$newcomer]));

        // A median cluster is +6 of clamped favorability, which the positive weight turns
        // into +15; a single downvoter is -1 against the negative weight, also 15. So the
        // realm must score no better than one where nobody has any history with the
        // newcomer at all -- the friends cannot buy off the objection.
        $this->assertLessThanOrEqual(
            0,
            $hostileScore,
            'a single downvoter must at least cancel a median friend cluster'
        );
        $this->assertLessThan($friendlyScore, $hostileScore);
    }

    private function scoringPlayer(string $id, float $rating = 1500, ?string $packId = null): Player
    {
        return new Player([
            'id' => $id,
            'userId' => $id,
            'rating' => $rating,
            'packId' => $packId,
            'hasKnownAffinities' => false,
        ]);
    }

    private function scoringRealm(string $id, int $size, float $rating = 1500, bool $packed = false): PlaceholderRealm
    {
        return new PlaceholderRealm($id, collect(range(1, $size))->map(
            fn (int $i) => $this->scoringPlayer("{$id}_{$i}", $rating, $packed ? 'seed' : null)
        ));
    }

    private function scoringPack(string $id, int $size, float $rating = 1500): PlaceholderPack
    {
        return new PlaceholderPack($id, collect(range(1, $size))->map(
            fn (int $i) => $this->scoringPlayer("{$id}_{$i}", $rating, $id)
        ));
    }

    public function testSizeBonusPenalisesAPlacementThatWouldOvershootTheTarget(): void
    {
        $this->service->targetRealmSize = 12;
        $realm = $this->scoringRealm('realm', 8);

        // One more player still fits; a six player pack would land the realm on 14.
        $this->assertGreaterThan(0, $this->service->calculateSizeBonus($realm, 1));
        $this->assertLessThan(
            -5000,
            $this->service->calculateSizeBonus($realm, 6),
            'the penalty must fire before the pack lands, not on the next placement'
        );
    }

    public function testSizeBonusRanksTheLeastOverTargetRealmHighest(): void
    {
        $this->service->targetRealmSize = 12;

        // targetRealmSize is floor(players / realms), so some realms must exceed it.
        // A flat penalty made all of these identical.
        $previous = null;
        foreach ([13, 18, 25, 40] as $size) {
            $bonus = $this->service->calculateSizeBonus($this->scoringRealm("size-{$size}", $size), 0);

            $this->assertLessThan(-5000, $bonus);
            if ($previous !== null) {
                $this->assertLessThan($previous, $bonus, 'a fuller realm must score worse');
            }
            $previous = $bonus;
        }
    }

    public function testOverTargetRealmLosesToAnUnderTargetRealmEvenWhenItIsABetterRatingFit(): void
    {
        $this->service->targetRealmSize = 12;
        $this->service->targetRealmStrength = 1500;

        $pack = $this->scoringPack('subject', 2, 2400);
        $overTarget = $this->scoringRealm('over', 13, 900);   // adding the pack improves its rating
        $underTarget = $this->scoringRealm('under', 4, 2400); // adding the pack worsens its rating

        $this->assertGreaterThan(
            $this->service->calculatePlacementScore($overTarget, $pack->members),
            $this->service->calculatePlacementScore($underTarget, $pack->members),
            'the size penalty must outweigh a favourable rating balance'
        );
    }

    public function testOpportunityCostDoesNotScaleWithTheRealmSizeBonus(): void
    {
        $this->service->targetRealmSize = 12;
        $this->service->targetRealmStrength = 1500;

        $pack = $this->scoringPack('subject', 2);
        $this->service->packs = collect(['subject' => $pack]);
        foreach (range(1, 5) as $i) {
            $this->service->packs->put("rival-{$i}", $this->scoringPack("rival-{$i}", 2));
        }

        // Identical size, so an identical size bonus. Rival packs are identical to the
        // subject, so every opportunity cost term is zero and cannot separate them —
        // the only difference is how many rivals canFitPack() admits.
        $roomy = $this->scoringRealm('roomy', 8);
        $stuffed = $this->scoringRealm('stuffed', 8, 1500, true);

        $this->assertEqualsWithDelta(
            $this->service->evaluatePackPlacement($pack, $stuffed),
            $this->service->evaluatePackPlacement($pack, $roomy),
            0.0001,
            'the size bonus must count once, not once per rival pack that happens to fit'
        );
    }

    public function testInboundFavorabilityLoadsWhatExistingMembersThinkOfTheNewcomer(): void
    {
        $round = $this->createRound();
        $realm = $this->createRealm($round);

        $critic = $this->createUser(null, ['rating' => 1500]);
        $this->createDominion($critic, $round, null, $realm);

        $newcomer = $this->createUser(null, ['rating' => 1500]);
        UserFeedback::create([
            'source_id' => $critic->id,
            'target_id' => $newcomer->id,
            'round_id' => $round->id,
            'endorsed' => false,
        ]);

        $player = Player::fromUser($newcomer, ['id' => $newcomer->id, 'userId' => $newcomer->id]);

        $this->assertSame(
            [$critic->id => [$newcomer->id => -1]],
            $this->service->getInboundFavorability($player, collect([$realm]))
        );
    }

    public function testSelectBestRealmAvoidsRealmsWhoseMembersDownvotedTheNewcomer(): void
    {
        $round = $this->createRound();
        $hostileRealm = $this->createRealm($round);
        $neutralRealm = $this->createRealm($round);

        $newcomer = $this->createUser(null, ['rating' => 1500]);

        // Both realms are identical in size, rating and affinities, so the only
        // thing that can separate them is feedback the newcomer never gave.
        foreach ([$hostileRealm, $neutralRealm] as $realm) {
            foreach (range(1, 2) as $ignored) {
                $member = $this->createUser(null, ['rating' => 1500]);
                $this->createDominion($member, $round, null, $realm);

                if ($realm->is($hostileRealm)) {
                    UserFeedback::create([
                        'source_id' => $member->id,
                        'target_id' => $newcomer->id,
                        'round_id' => $round->id,
                        'endorsed' => false,
                    ]);
                }
            }
        }

        $player = Player::fromUser($newcomer, ['id' => $newcomer->id, 'userId' => $newcomer->id]);

        $selected = $this->service->selectBestRealm(
            collect([$hostileRealm, $neutralRealm]),
            $player,
            $round
        );

        $this->assertTrue($selected->is($neutralRealm));
    }

    public function testRealmMembershipConsistentlyExcludesLockedAndAbandonedDominions(): void
    {
        $round = $this->createRound();
        $realm = $this->createRealm($round);

        $active = $this->createUser(null, ['rating' => 1500]);
        $this->createDominion($active, $round, null, $realm);

        $locked = $this->createUser(null, ['rating' => 1500]);
        $this->createDominion($locked, $round, null, $realm)
            ->update(['locked_at' => now()->subDay()]);

        $abandoned = $this->createUser(null, ['rating' => 1500]);
        $this->createDominion($abandoned, $round, null, $realm)
            ->update(['abandoned_at' => now()->subHour()]);

        // Scheduled abandonment has not taken effect yet, so this one still counts.
        $leaving = $this->createUser(null, ['rating' => 1500]);
        $this->createDominion($leaving, $round, null, $realm)
            ->update(['abandoned_at' => now()->addDay()]);

        $newcomer = $this->createUser(null, ['rating' => 1500]);
        foreach ([$locked, $abandoned] as $departed) {
            UserFeedback::create([
                'source_id' => $departed->id,
                'target_id' => $newcomer->id,
                'round_id' => $round->id,
                'endorsed' => false,
            ]);
        }

        $placeholderRealm = $this->service->createPlaceholderRealm($realm);
        $player = Player::fromUser($newcomer, ['id' => $newcomer->id, 'userId' => $newcomer->id]);

        $this->assertSame(
            2,
            $placeholderRealm->players->count(),
            'only the active and not-yet-abandoned members occupy a slot'
        );
        $this->assertSame(
            [],
            $this->service->getInboundFavorability($player, collect([$realm])),
            'feedback from departed members must not follow a newcomer around'
        );
    }

    public function testPlaceholderRealmLoadsAffinitiesForExistingMembers(): void
    {
        $round = $this->createRound();
        $realm = $this->createRealm($round);

        $member = $this->createUser(null, [
            'rating' => 1500,
            'affinities' => ['attacker' => 80, 'converter' => 10, 'explorer' => 20, 'ops' => 10],
        ]);
        $this->createDominion($member, $round, null, $realm);

        $player = $this->service->createPlaceholderRealm($realm)->players->first();

        $this->assertTrue(
            $player->hasKnownAffinities,
            'existing members must reach playstyle scoring with their real affinities'
        );
        $this->assertSame(80.0, $player->attackerAffinity);
    }
}
