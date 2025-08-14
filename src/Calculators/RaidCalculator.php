<?php

namespace OpenDominion\Calculators;

use Illuminate\Support\Collection;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\OpsCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Raid;
use OpenDominion\Models\RaidContribution;
use OpenDominion\Models\RaidObjective;
use OpenDominion\Models\RaidObjectiveTactic;
use OpenDominion\Models\Realm;

class RaidCalculator
{
    /** @var LandCalculator */
    protected $landCalculator;

    /** @var OpsCalculator */
    protected $opsCalculator;

    // Reward calculation constants
    const MAX_REALM_REWARD_RATIO = 0.15;          // Max 15% of total pool per realm
    const MAX_PLAYER_REWARD_RATIO = 0.15;         // Max 15% of required score per player
    const COMPLETION_REWARD_SCALING = true;      // true = percentage-based, false = binary (all-or-nothing)

    public function __construct()
    {
        $this->landCalculator = app(LandCalculator::class);
        $this->opsCalculator = app(OpsCalculator::class);
    }

    /**
     * Get the mana cost for a tactic spell option.
     */
    public function getTacticManaCost(Dominion $dominion, RaidObjectiveTactic $tactic): int
    {
        $manaCostMultiplier = $tactic->attributes['mana_cost'];
        $totalLand = $this->landCalculator->getTotalLand($dominion);
        return rceil($manaCostMultiplier * $totalLand);
    }

    /**
     * Get the actual points earned for a tactic action.
     */
    public function getTacticPointsEarned(Dominion $dominion, RaidObjectiveTactic $tactic): float
    {
        $basePoints = $tactic->attributes['points_awarded'];

        // Apply espionage score multiplier for espionage tactics
        if ($tactic->type === 'espionage') {
            $multiplier = $this->opsCalculator->getEspionageScoreMultiplier($dominion);
            return $basePoints * $multiplier;
        }

        // Apply magic score multiplier for magic tactics
        if ($tactic->type === 'magic') {
            $multiplier = $this->opsCalculator->getMagicScoreMultiplier($dominion);
            return $basePoints * $multiplier;
        }

        return $basePoints;
    }

    /**
     * Get the score for a raid objective.
     * If no realm is provided, returns the total score for all realms.
     * If a realm is provided, returns the score for that specific realm.
     */
    public function getObjectiveScore(RaidObjective $objective, ?Realm $realm = null): int
    {
        $query = RaidContribution::where('raid_objective_id', $objective->id);

        if ($realm !== null) {
            $query->where('realm_id', $realm->id);
        }

        return $query->sum('score');
    }

    /**
     * Get the progress percentage for a raid objective.
     * If no realm is provided, returns the progress for all realms combined.
     * If a realm is provided, returns the progress for that specific realm.
     */
    public function getObjectiveProgress(RaidObjective $objective, ?Realm $realm = null): float
    {
        $currentScore = $this->getObjectiveScore($objective, $realm);
        $requiredScore = $objective->score_required;

        if ($requiredScore <= 0) {
            return 0;
        }

        return min(100, ($currentScore / $requiredScore) * 100);
    }

    /**
     * Check if a raid objective is completed.
     * If no realm is provided, checks if any realm has completed it.
     * If a realm is provided, checks if that specific realm has completed it.
     */
    public function isObjectiveCompleted(RaidObjective $objective, ?Realm $realm = null): bool
    {
        return $this->getObjectiveScore($objective, $realm) >= $objective->score_required;
    }

    /**
     * Get the total contribution for a dominion in a raid objective.
     */
    public function getDominionContribution(RaidObjective $objective, Dominion $dominion): int
    {
        return RaidContribution::where('raid_objective_id', $objective->id)
            ->where('dominion_id', $dominion->id)
            ->sum('score');
    }

    /**
     * Get the total contribution for a realm in a raid objective.
     */
    public function getRealmContribution(RaidObjective $objective, Realm $realm): int
    {
        return RaidContribution::where('raid_objective_id', $objective->id)
            ->where('realm_id', $realm->id)
            ->sum('score');
    }

    /**
     * Get the contribution percentage for a dominion in a raid objective.
     */
    public function getDominionContributionPercentage(RaidObjective $objective, Dominion $dominion): float
    {
        $totalScore = $this->getObjectiveScore($objective);
        $dominionScore = $this->getDominionContribution($objective, $dominion);

        if ($totalScore <= 0) {
            return 0;
        }

        return ($dominionScore / $totalScore) * 100;
    }

    /**
     * Get the contribution percentage for a realm in a raid objective.
     */
    public function getRealmContributionPercentage(RaidObjective $objective, Realm $realm): float
    {
        $totalScore = $this->getObjectiveScore($objective);
        $realmScore = $this->getRealmContribution($objective, $realm);

        if ($totalScore <= 0) {
            return 0;
        }

        return ($realmScore / $totalScore) * 100;
    }

    /**
     * Get recent contributions for a raid objective from a specific realm.
     */
    public function getRecentContributions(RaidObjective $objective, Realm $realm, int $limit = 10): array
    {
        return RaidContribution::where('raid_objective_id', $objective->id)
            ->where('realm_id', $realm->id)
            ->with(['dominion'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($contribution) {
                return [
                    'dominion_name' => $contribution->dominion->name,
                    'type' => $contribution->type,
                    'score' => $contribution->score,
                    'created_at' => $contribution->created_at,
                ];
            })
            ->toArray();
    }

    /**
     * Get top contributors for a raid objective.
     */
    public function getTopContributors(RaidObjective $objective, int $limit = 10): array
    {
        return RaidContribution::where('raid_objective_id', $objective->id)
            ->with(['dominion', 'dominion.realm'])
            ->selectRaw('dominion_id, SUM(score) as total_score')
            ->groupBy('dominion_id')
            ->orderBy('total_score', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($contribution) {
                return [
                    'dominion_name' => $contribution->dominion->name,
                    'realm_name' => $contribution->dominion->realm->name,
                    'total_score' => $contribution->total_score,
                ];
            })
            ->toArray();
    }

    /**
     * Get realm leaderboard for a raid objective.
     */
    public function getRealmsLeaderboard(RaidObjective $objective): array
    {
        return RaidContribution::where('raid_objective_id', $objective->id)
            ->with(['realm'])
            ->selectRaw('realm_id, SUM(score) as total_score')
            ->groupBy('realm_id')
            ->orderBy('total_score', 'desc')
            ->get()
            ->map(function ($contribution) use ($objective) {
                $progress = min(100, ($contribution->total_score / $objective->score_required) * 100);
                return [
                    'realm_id' => $contribution->realm_id,
                    'realm_name' => $contribution->realm->name,
                    'realm_number' => $contribution->realm->number,
                    'total_score' => $contribution->total_score,
                    'progress' => $progress,
                    'completed' => $contribution->total_score >= $objective->score_required,
                ];
            })
            ->toArray();
    }

    /**
     * Get realms that have completed the objective.
     */
    public function getCompletedRealms(RaidObjective $objective): array
    {
        return RaidContribution::where('raid_objective_id', $objective->id)
            ->with(['realm'])
            ->selectRaw('realm_id, SUM(score) as total_score')
            ->groupBy('realm_id')
            ->havingRaw('SUM(score) >= ?', [$objective->score_required])
            ->orderBy('total_score', 'desc')
            ->get()
            ->map(function ($contribution) {
                return [
                    'realm_id' => $contribution->realm_id,
                    'realm_name' => $contribution->realm->name,
                    'total_score' => $contribution->total_score,
                ];
            })
            ->toArray();
    }

    /**
     * Get raid-level leaderboard showing objective completion progress.
     */
    public function getRaidLeaderboard(Raid $raid): array
    {
        $objectiveIds = $raid->objectives->pluck('id');
        $totalObjectives = $raid->objectives->count();

        // Get all realms that participated in this raid
        $participatingRealms = RaidContribution::whereIn('raid_objective_id', $objectiveIds)
            ->with(['realm'])
            ->selectRaw('realm_id, SUM(score) as total_score')
            ->groupBy('realm_id')
            ->orderBy('total_score', 'desc')
            ->get();

        $leaderboardData = $participatingRealms->map(function ($realmData) use ($raid, $totalObjectives) {
            $realm = $realmData->realm;
            $completedObjectives = 0;

            // Count how many objectives this realm has completed
            foreach ($raid->objectives as $objective) {
                if ($this->isObjectiveCompleted($objective, $realm)) {
                    $completedObjectives++;
                }
            }

            $completionPercentage = $totalObjectives > 0 ? ($completedObjectives / $totalObjectives) * 100 : 0;

            return [
                'realm_id' => $realm->id,
                'realm_name' => $realm->name,
                'realm_number' => $realm->number,
                'total_score' => $realmData->total_score,
                'completed_objectives' => $completedObjectives,
                'total_objectives' => $totalObjectives,
                'completion_percentage' => $completionPercentage,
                'fully_completed' => $completedObjectives === $totalObjectives,
            ];
        })
        ->sortByDesc('completed_objectives')  // Sort by objectives completed first
        ->sortByDesc('total_score')          // Then by total score as tiebreaker
        ->values();

        // Calculate exact participation pool shares using the existing reward distribution method
        $totalScore = $leaderboardData->sum('total_score');

        // Build contribution data structure that calculateRealmRewardPools expects
        $contributionData = [
            'total' => $totalScore,
            'by_realm' => $leaderboardData->pluck('total_score', 'realm_id')->toArray(),
        ];

        // Use the actual reward calculation method
        $realmPools = $this->calculateRealmRewardPools($raid, $contributionData);

        return $leaderboardData->map(function ($entry) use ($realmPools, $totalScore) {
            $realmId = $entry['realm_id'];
            $contributionPercentage = $totalScore > 0 ? ($entry['total_score'] / $totalScore) * 100 : 0;

            $entry['estimated_participation_reward'] = $realmPools[$realmId] ?? 0;
            $entry['contribution_percentage'] = $contributionPercentage;

            return $entry;
        })->toArray();
    }

    /**
     * Get player-level reward breakdown for a specific realm using actual reward calculation logic.
     */
    public function getRealmPlayerBreakdown(Raid $raid, Realm $realm): array
    {
        // Get raid contribution data using the same method as reward calculation
        $contributionData = $this->getRaidContributionData($raid);

        // Calculate realm pools using the actual reward method
        $realmPools = $this->calculateRealmRewardPools($raid, $contributionData);

        // Calculate player allocations using the actual reward method
        $playerAllocations = $this->calculatePlayerRewardAllocations($raid, $contributionData, $realmPools);

        // Get player contributions for this specific realm
        $realmPlayerContributions = RaidContribution::whereIn('raid_objective_id', $raid->objectives->pluck('id'))
            ->where('realm_id', $realm->id)
            ->with(['dominion'])
            ->selectRaw('dominion_id, SUM(score) as total_score')
            ->groupBy('dominion_id')
            ->orderBy('total_score', 'desc')
            ->get();

        $realmTotalScore = $contributionData['by_realm'][$realm->id] ?? 0;

        return $realmPlayerContributions->map(function ($playerData) use ($playerAllocations, $realmTotalScore, $realm) {
            $dominionId = $playerData->dominion_id;
            $playerScore = $playerData->total_score;
            $estimatedReward = $playerAllocations[$dominionId] ?? 0;
            $percentageOfRealm = $realmTotalScore > 0 ? ($playerScore / $realmTotalScore) * 100 : 0;

            // Data integrity check - skip dominions that don't actually belong to the requested realm
            if ($playerData->dominion->realm_id !== $realm->id) {
                return null; // Will be filtered out
            }

            return [
                'dominion_id' => $dominionId,
                'dominion_name' => $playerData->dominion->name,
                'total_score' => $playerScore,
                'estimated_reward' => $estimatedReward,
                'percentage_of_realm' => $percentageOfRealm,
            ];
        })->filter()->toArray();
    }

    /**
     * Get top contributors for a raid objective within a specific realm.
     */
    public function getTopContributorsInRealm(RaidObjective $objective, Realm $realm, int $limit = 10): array
    {
        return RaidContribution::where('raid_objective_id', $objective->id)
            ->where('realm_id', $realm->id)
            ->with(['dominion'])
            ->selectRaw('dominion_id, SUM(score) as total_score')
            ->groupBy('dominion_id')
            ->orderBy('total_score', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($contribution) {
                return [
                    'dominion_name' => $contribution->dominion->name,
                    'total_score' => $contribution->total_score,
                ];
            })
            ->toArray();
    }

    /**
     * Calculate and distribute rewards for a completed raid using two-tier system.
     * Uses bulk data loading for optimal performance.
     */
    public function calculateRaidRewards(Raid $raid): array
    {
        // Step 1: Load all contribution data
        $contributionData = $this->getRaidContributionData($raid);

        // Step 2: Calculate realm completion data for completion rewards
        $realmCompletionData = $this->calculateRealmCompletionData($raid, $contributionData);

        // Step 3: Calculate realm reward pools (two-tier system)
        $realmPools = $this->calculateRealmRewardPools($raid, $contributionData);

        // Step 4: Calculate player allocations within realm pools
        $playerAllocations = $this->calculatePlayerRewardAllocations($raid, $contributionData, $realmPools);

        // Step 5: Get all participating dominions
        $participatingDominions = $this->getParticipatingDominions($raid);

        // Step 6: Build reward data for each dominion
        $rewardData = [];
        foreach ($participatingDominions as $dominion) {
            $participationReward = $this->calculateParticipationReward($raid, $dominion, $playerAllocations);
            $completionReward = $this->calculateCompletionReward($raid, $dominion, $realmCompletionData);

            $rewardData[] = [
                'dominion' => $dominion,
                'participation_reward' => $participationReward,
                'completion_reward' => $completionReward,
            ];
        }

        return $rewardData;
    }

    /**
     * Calculate participation reward for a dominion using pre-calculated allocations.
     */
    public function calculateParticipationReward(Raid $raid, Dominion $dominion, array $playerAllocations): array
    {
        $rewardAmount = $playerAllocations[$dominion->id] ?? 0;

        return [
            'resource' => $raid->reward_resource,
            'amount' => (int) $rewardAmount,
            'bonuses_applied' => [], // No bonuses in new system - distribution is built into allocation
        ];
    }

    /**
     * Calculate completion reward - supports both binary and percentage-based scaling.
     */
    public function calculateCompletionReward(Raid $raid, Dominion $dominion, array $realmCompletionData): array
    {
        $completionData = $realmCompletionData[$dominion->realm_id] ?? [
            'completion_percentage' => 0,
            'all_completed' => false,
        ];

        $bonusesApplied = [];

        if (self::COMPLETION_REWARD_SCALING) {
            // Percentage-based: Scale reward by completion percentage
            $rewardAmount = (int) ($completionData['completion_percentage'] * $raid->completion_reward_amount);

            if ($completionData['completion_percentage'] >= 1.0) {
                $bonusesApplied[] = 'full_completion';
            } elseif ($completionData['completion_percentage'] > 0) {
                $bonusesApplied[] = 'partial_completion';
            }
        } else {
            // Binary: All-or-nothing (current behavior)
            $rewardAmount = $completionData['all_completed'] ? $raid->completion_reward_amount : 0;
        }

        return [
            'resource' => $raid->completion_reward_resource,
            'amount' => $rewardAmount,
            'bonuses_applied' => $bonusesApplied,
        ];
    }

    /**
     * Get all dominions that participated in the raid.
     */
    protected function getParticipatingDominions(Raid $raid): Collection
    {
        return RaidContribution::whereIn('raid_objective_id', $raid->objectives->pluck('id'))
            ->with(['dominion'])
            ->get()
            ->pluck('dominion')
            ->unique('id')
            ->values();
    }

    /**
     * Get all contribution data for a raid in a single query for efficiency.
     * Returns structured data to eliminate N+1 query patterns.
     */
    protected function getRaidContributionData(Raid $raid): array
    {
        $objectiveIds = $raid->objectives->pluck('id');

        // Single query to get all contribution data
        $contributions = RaidContribution::whereIn('raid_objective_id', $objectiveIds)
            ->selectRaw('dominion_id, realm_id, raid_objective_id, SUM(score) as total_score')
            ->groupBy(['dominion_id', 'realm_id', 'raid_objective_id'])
            ->get();

        $data = [
            'total' => 0,
            'by_dominion' => [],
            'by_realm' => [],
            'by_realm_objective' => [], // For completion calculations
            'dominion_realm_map' => [], // Maps dominion_id to realm_id
        ];

        foreach ($contributions as $contribution) {
            $dominionId = $contribution->dominion_id;
            $realmId = $contribution->realm_id;
            $objectiveId = $contribution->raid_objective_id;
            $score = $contribution->total_score;

            // Aggregate totals
            $data['total'] += $score;
            $data['by_dominion'][$dominionId] = ($data['by_dominion'][$dominionId] ?? 0) + $score;
            $data['by_realm'][$realmId] = ($data['by_realm'][$realmId] ?? 0) + $score;

            // Track realm scores per objective for completion calculations
            if (!isset($data['by_realm_objective'][$realmId])) {
                $data['by_realm_objective'][$realmId] = [];
            }
            $data['by_realm_objective'][$realmId][$objectiveId] = ($data['by_realm_objective'][$realmId][$objectiveId] ?? 0) + $score;

            // Map dominion to realm for efficient lookups
            $data['dominion_realm_map'][$dominionId] = $realmId;
        }

        return $data;
    }

    /**
     * Calculate realm completion data for all participating realms.
     * Uses contribution data to determine completion percentages efficiently.
     */
    protected function calculateRealmCompletionData(Raid $raid, array $contributionData): array
    {
        $completionData = [];
        $realmObjectiveData = $contributionData['by_realm_objective'];

        foreach ($realmObjectiveData as $realmId => $objectiveScores) {
            $completedObjectives = 0;
            $totalObjectives = $raid->objectives->count();

            foreach ($raid->objectives as $objective) {
                $realmScore = $objectiveScores[$objective->id] ?? 0;
                if ($realmScore >= $objective->score_required) {
                    $completedObjectives++;
                }
            }

            $completionPercentage = $totalObjectives > 0 ? ($completedObjectives / $totalObjectives) : 0;

            $completionData[$realmId] = [
                'completed_objectives' => $completedObjectives,
                'total_objectives' => $totalObjectives,
                'completion_percentage' => $completionPercentage,
                'all_completed' => $completedObjectives === $totalObjectives,
            ];
        }

        return $completionData;
    }

    /**
     * Calculate realm bonus eligibility data for all participating realms.
     * Uses contribution data to determine excess bonus eligibility efficiently.
     */
    protected function calculateRealmBonusData(Raid $raid, array $contributionData): array
    {
        $bonusData = [];
        $realmObjectiveData = $contributionData['by_realm_objective'];

        foreach ($realmObjectiveData as $realmId => $objectiveScores) {
            $exceededRequirements = false;

            foreach ($raid->objectives as $objective) {
                $realmScore = $objectiveScores[$objective->id] ?? 0;
                $required = $objective->score_required;

                if ($realmScore >= $required * 1.5) { // Exceeded by 50%
                    $exceededRequirements = true;
                    break;
                }
            }

            $bonusData[$realmId] = [
                'excess_bonus_eligible' => $exceededRequirements,
            ];
        }

        return $bonusData;
    }

    /**
     * Calculate realm reward pools using two-tier distribution system.
     * Each realm gets up to 10% of the total pool based on contribution.
     * Remaining pool is distributed equally among all participating realms.
     */
    protected function calculateRealmRewardPools(Raid $raid, array $contributionData): array
    {
        $totalPool = $raid->reward_amount;
        $totalContributions = $contributionData['total'];
        $realmContributions = $contributionData['by_realm'];

        if ($totalContributions == 0 || empty($realmContributions)) {
            return [];
        }

        $realmPools = [];
        $allocatedAmount = 0;
        $maxRealmAmount = $totalPool * self::MAX_REALM_REWARD_RATIO;

        // Step 1: Allocate based on contribution percentage (capped at 10%)
        foreach ($realmContributions as $realmId => $realmContribution) {
            $contributionPercentage = $realmContribution / $totalContributions;
            $proportionalAmount = $contributionPercentage * $totalPool;
            $cappedAmount = min($proportionalAmount, $maxRealmAmount);

            $realmPools[$realmId] = $cappedAmount;
            $allocatedAmount += $cappedAmount;
        }

        // Step 2: Distribute remaining pool equally among all realms
        $remainingPool = $totalPool - $allocatedAmount;
        if ($remainingPool > 0 && count($realmPools) > 0) {
            $equalShare = $remainingPool / count($realmPools);
            foreach ($realmPools as $realmId => $currentAmount) {
                $realmPools[$realmId] = $currentAmount + $equalShare;
            }
        }

        return $realmPools;
    }

    /**
     * Calculate player reward allocations within their realm pools.
     * Each player gets up to 15% of the realm's total contribution based on their individual contribution.
     * Remaining realm pool is distributed equally among realm players.
     */
    protected function calculatePlayerRewardAllocations(Raid $raid, array $contributionData, array $realmPools): array
    {
        $playerAllocations = [];

        foreach ($realmPools as $realmId => $realmPool) {
            if ($realmPool <= 0) {
                continue;
            }

            // Get all players in this realm using the dominion_realm_map
            $realmPlayers = [];
            $realmTotalContributions = $contributionData['by_realm'][$realmId] ?? 0;

            foreach ($contributionData['by_dominion'] as $dominionId => $dominionContribution) {
                $dominionRealmId = $contributionData['dominion_realm_map'][$dominionId] ?? null;
                if ($dominionRealmId == $realmId) {
                    $realmPlayers[$dominionId] = $dominionContribution;
                }
            }

            if (empty($realmPlayers) || $realmTotalContributions == 0) {
                continue;
            }

            // Calculate the maximum amount a single player can get (15% of realm's total contribution)
            $maxPlayerAmount = $realmTotalContributions * self::MAX_PLAYER_REWARD_RATIO;

            $realmAllocatedAmount = 0;

            // Step 1: Allocate based on contribution percentage within realm (capped at 15% of realm total)
            foreach ($realmPlayers as $dominionId => $dominionContribution) {
                $contributionPercentage = $dominionContribution / $realmTotalContributions;
                $proportionalAmount = $contributionPercentage * $realmPool;
                $cappedAmount = min($proportionalAmount, $maxPlayerAmount);

                $playerAllocations[$dominionId] = $cappedAmount;
                $realmAllocatedAmount += $cappedAmount;
            }

            // Step 2: Distribute remaining realm pool equally among realm players
            $remainingRealmPool = $realmPool - $realmAllocatedAmount;
            if ($remainingRealmPool > 0 && count($realmPlayers) > 0) {
                $equalShare = $remainingRealmPool / count($realmPlayers);
                foreach ($realmPlayers as $dominionId => $contribution) {
                    $playerAllocations[$dominionId] += $equalShare;
                }
            }
        }

        return $playerAllocations;
    }
}
