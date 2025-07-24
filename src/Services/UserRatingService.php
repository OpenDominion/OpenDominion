<?php

namespace OpenDominion\Services;

use Illuminate\Support\Facades\DB;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundLeague;
use OpenDominion\Models\User;

/**
 * Service for calculating and managing user ratings
 *
 * This service calculates a user's skill rating based on their performance
 * across multiple rounds, similar to ELO or other rating systems used in
 * competitive games.
 */
class UserRatingService
{
    const DEFAULT_RATING = 1000.0;

    protected $landCalculator;

    public function __construct(LandCalculator $landCalculator)
    {
        $this->landCalculator = $landCalculator;
    }

    /**
     * Update a user's rating
     *
     * @param User $user
     * @return bool True if rating was updated, false otherwise
     */
    public function updateUserRatings(User $user): bool
    {
        $ratings = $this->calculateUserRatings($user);

        $oldRating = $user->rating ?? self::DEFAULT_RATING;
        $newRating = $ratings['rating'];
        $rankingChanged = ($newRating !== $oldRating);

        $user->rating = $newRating;
        $user->affinities = $ratings['affinities'];
        $user->save();

        return $rankingChanged;
    }

    /**
     * Update all users' ratings after a round ends
     *
     * @param Round $round
     * @return array
     */
    public function updateRatingsAfterRound(Round $round): array
    {
        $usersProcessed = 0;
        $ratingsUpdated = 0;

        $dominions = $round->dominions()->human()->get();

        foreach ($dominions as $dominion) {
            $result = $this->updateUserRatings($dominion->user);
            if ($result) {
                $ratingsUpdated++;
            }
            $usersProcessed++;
        }

        return [
            'users_processed' => $usersProcessed,
            'ratings_updated' => $ratingsUpdated,
        ];
    }

    /**
     * Update ratings for all users who have played at least one dominion
     *
     * This method iterates through all users that have at least one dominion
     * and triggers a rating calculation/update for each user.
     *
     * @param Round|null $round Optional specific round to process, or null for all rounds
     * @return array Summary of updates performed
     */
    public function updateAllUserRatings(): array
    {
        $usersProcessed = 0;
        $ratingsUpdated = 0;

        // Get all users who have at least one dominion
        $users = User::whereHas('dominions')->get();

        foreach ($users as $user) {
            $result = $this->updateUserRatings($user);
            if ($result) {
                $ratingsUpdated++;
            }
            $usersProcessed++;
        }

        return [
            'users_processed' => $usersProcessed,
            'ratings_updated' => $ratingsUpdated,
        ];
    }

    /**
     * Calculate a user's current rating
     *
     * @param User $user
     * @return array The calculated ratings
     */
    public function calculateUserRatings(User $user): array
    {
        // Get scores for user's most recent dominions
        $dominionScores = $this->calculateDominionScores($user);

        // Get a user's playstyle affinities
        $affinities = $this->calculateAffinities($dominionScores);

        // Average the best finishes to determine overall rating
        $rating = $this->averageBestFinishes($dominionScores);

        // Feedback score (0-100)
        $feedbackScore = $this->calculateFeedbackScore($user->id);

        return [
            'rating' => $rating + $feedbackScore,
            'affinities' => $affinities,
        ];
    }

    /**
     * Calculate performance scores for a user's most recent dominions
     *
     * This method gets the user's recent dominions and calculates a performance
     * score for each based on their final position, competition level, and other factors.
     *
     * @param User $user
     * @param int $maxDominions Maximum number of recent dominions to consider
     * @return array Array of dominion scores with metadata
     */
    public function calculateDominionScores(User $user, int $maxDominions = 10): array
    {
        $standardLeague = RoundLeague::where('key', 'standard')->first();
        if (!$standardLeague) {
            return [];
        }

        $roundIds = $standardLeague->rounds()->pluck('id');

        // Get user's most recent dominions
        $dominions = $user->dominions()
            ->whereIn('round_id', $roundIds)
            ->orderBy('created_at', 'desc')
            ->limit($maxDominions)
            ->get();

        $scores = [];

        foreach ($dominions as $dominion) {
            $scores[] = $this->calculateSingleDominionScore($dominion);
        }

        return $scores;
    }

    /**
     * Calculate performance score for a single dominion
     *
     * @param Dominion $dominion
     * @return array Performance scores for this dominion
     */
    public function calculateSingleDominionScore(Dominion $dominion): array
    {
        // Get basic performance metrics
        $metrics = $this->getPerformanceMetrics($dominion);

        // Determine playstyle
        $isAttacker = false;
        $isConverter = false;
        $isExplorer = true;
        if ($metrics['land_conquered'] > 200) {
            $isAttacker = true;
            $isExplorer = false;
        }
        if ($isAttacker && $metrics['land_conquered_ratio'] < 0.6) {
            $isConverter = true;
        }
        $isOps = $metrics['espionage_ops'] > 1000 && $metrics['magic_ops'] > 500;

        // Base score from ranking (0-2000 range)
        // Examples: Rank 1 = ~2000, Rank 10 = ~1800, Rank 25 = ~1500, Rank 50 = ~1000, Rank 100 = ~500
        $score = 2000 * exp(-0.005 * ($metrics['rank'] - 1));

        // Significant performance bonuses (max ~1000 points total)
        // Land conquered bonus (0-500 points)
        $score += min(500, $metrics['land_conquered'] / 10);

        // Ops performance bonus (0-200 points)
        $score += min(200, ($metrics['espionage_ops'] + $metrics['magic_ops']) / 30);

        // Bounties bonus (0-100 points)
        $score += min(100, $metrics['bounties_collected'] / 30);

        // Activity bonus (0-200 points) - percentage of maximum possible activity
        $activityRatio = min(1.0, $metrics['hourly_activity'] / 1128);
        $score += $activityRatio * 200;

        return [
            'score' => $score,
            'attacker' => $isAttacker,
            'explorer' => $isExplorer,
            'converter' => $isConverter,
            'ops' => $isOps,
        ];
    }

    /**
     * Calculate feedback score for a user
     *
     * @param int $userId
     * @return float Feedback score
     */
    public function calculateFeedbackScore($userId): float
    {
        $feedback = DB::table('user_feedback')->selectRaw('endorsed')->where('target_id', $userId)->get();
        $positiveFeedback = $feedback->where('endorsed', 1)->count();
        $negativeFeedback = $feedback->where('endorsed', 0)->count();

        $netFeedback = $positiveFeedback - $negativeFeedback;

        // Scale to ±100 points max
        if ($netFeedback > 0) {
            return min(100, $netFeedback * 10); // +10 per net positive
        } else {
            return max(-100, $netFeedback * 10); // -10 per net negative
        }
    }

    /**
     * Calculate a user's playstyle affinities
     *
     * @param array $dominionScores Array of dominion score data
     * @return array Playstyle affinities
     */
    public function calculateAffinities(array $dominionScores): array
    {
        $totalScores = count($dominionScores);

        if ($totalScores === 0) {
            return [
                'attacker' => 0,
                'explorer' => 0,
                'converter' => 0,
                'ops' => 0,
            ];
        }

        $attackerCount = 0;
        $explorerCount = 0;
        $converterCount = 0;
        $opsCount = 0;

        foreach ($dominionScores as $dominionScore) {
            $attackerCount += $dominionScore['attacker'] ? 1 : 0;
            $explorerCount += $dominionScore['explorer'] ? 1 : 0;
            $converterCount += $dominionScore['converter'] ? 1 : 0;
            $opsCount += $dominionScore['ops'] ? 1 : 0;
        }

        return [
            'attacker' => round($attackerCount / $totalScores * 100, 2),
            'explorer' => round($explorerCount / $totalScores * 100, 2),
            'converter' => round($converterCount / $totalScores * 100, 2),
            'ops' => round($opsCount / $totalScores * 100, 2),
        ];
    }

    /**
     * Average the best finishes to determine overall rating
     *
     * Takes the dominion scores and calculates an average based
     * on the user's best performances.
     *
     * @param array $dominionScores Array of dominion score data
     * @param int $bestCount Number of best finishes to use for the average
     * @return float Calculated rating
     */
    public function averageBestFinishes(array $dominionScores, int $bestCount = 3): float
    {
        if (empty($dominionScores)) {
            return self::DEFAULT_RATING;
        }

        // Sort scores by performance (highest first)
        usort($dominionScores, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Focus on best performances with weighted averaging
        $totalScores = count($dominionScores);
        $bestScores = array_slice($dominionScores, 0, min($bestCount, $totalScores));

        return array_sum(array_column($bestScores, 'score')) / count($bestScores);
    }

    /**
     * Get performance metrics for a dominion
     *
     * @param Dominion $dominion
     * @return array Performance metrics
     */
    public function getPerformanceMetrics(Dominion $dominion): array
    {
        $rank = 100;

        $largestRanking = DB::table('daily_rankings')
            ->where('dominion_id', $dominion->id)
            ->where('key', 'largest-dominions')
            ->first();

        if ($largestRanking) {
            $rank = $largestRanking->rank;
        }

        $totalLand = $this->landCalculator->getTotalLand($dominion);
        if ($dominion->stat_total_land_lost >= $dominion->stat_total_land_conquered) {
            $conqueredLand = 0;
            $exploredLand = $totalLand - 250 + max(0, $dominion->stat_total_land_conquered - $dominion->stat_total_land_lost);
        } else {
            $conqueredLand = $dominion->stat_total_land_conquered - $dominion->stat_total_land_lost;
            $exploredLand = $totalLand - 250 - $conqueredLand;
        }

        return [
            'rank' => $rank,
            'networth' => $dominion->calculated_networth,
            'total_land' => $totalLand,
            'land_explored' => $exploredLand,
            'land_conquered' => $conqueredLand,
            'land_conquered_ratio' => $conqueredLand / $totalLand,
            'prestige' => $dominion->prestige,
            'successful_attacks' => $dominion->stat_attacking_success,
            'failed_defenses' => $dominion->stat_defending_failure,
            'espionage_ops' => $dominion->stat_espionage_success + $dominion->stat_espionage_failure,
            'magic_ops' => $dominion->stat_spell_success + $dominion->stat_spell_failure,
            'bounties_collected' => $dominion->stat_bounties_collected,
            'hourly_activity' => substr_count($dominion->hourly_activity, '1'),
        ];
    }
}
