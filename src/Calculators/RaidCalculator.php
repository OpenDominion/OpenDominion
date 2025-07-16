<?php

namespace OpenDominion\Calculators;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\OpsCalculator;
use Illuminate\Support\Collection;
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
    const PARTICIPATION_BONUS_MULTIPLIER = 1.5;  // +50% for high contribution (25%+ of total)
    const EXCESS_BONUS_MULTIPLIER = 1.25;        // +25% for exceeding requirements
    const MAX_INDIVIDUAL_REWARD_RATIO = 0.3;     // Max 30% of total reward pool per person
    const COMPLETION_REWARD_SCALING = false;     // true = percentage-based, false = binary (all-or-nothing)

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
     * Calculate and distribute rewards for a completed raid.
     * Uses bulk data loading for optimal performance.
     */
    public function calculateRaidRewards(Raid $raid): array
    {
        $contributionData = $this->getRaidContributionData($raid);
        $realmCompletionData = $this->calculateRealmCompletionData($raid, $contributionData);
        $realmBonusData = $this->calculateRealmBonusData($raid, $contributionData);
        $participatingDominions = $this->getParticipatingDominions($raid);

        $rewardData = [];
        foreach ($participatingDominions as $dominion) {
            $participationReward = $this->calculateParticipationReward($raid, $dominion, $contributionData, $realmBonusData);
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
     * Calculate participation reward for a dominion based on contribution.
     */
    public function calculateParticipationReward(Raid $raid, Dominion $dominion, array $contributionData, array $realmBonusData): array
    {
        $totalContributions = $contributionData['total'];
        $dominionContributions = $contributionData['by_dominion'][$dominion->id] ?? 0;

        if ($totalContributions == 0 || $dominionContributions == 0) {
            return [
                'resource' => $raid->reward_resource,
                'amount' => 0,
                'bonuses_applied' => [],
            ];
        }

        // Base reward calculation
        $baseReward = ($dominionContributions / $totalContributions) * $raid->reward_amount;
        $bonusesApplied = [];

        // Apply excess bonus if realm exceeded requirements
        $realmExceededRequirements = $realmBonusData[$dominion->realm_id]['excess_bonus_eligible'] ?? false;
        if ($realmExceededRequirements) {
            $baseReward *= self::EXCESS_BONUS_MULTIPLIER;
            $bonusesApplied[] = 'excess_bonus';
        }

        // Apply participation bonus for high contributors (25%+ of total)
        $contributionPercentage = ($dominionContributions / $totalContributions) * 100;
        if ($contributionPercentage >= 25) {
            $baseReward *= self::PARTICIPATION_BONUS_MULTIPLIER;
            $bonusesApplied[] = 'participation_bonus';
        }

        // Apply individual cap
        $maxReward = $raid->reward_amount * self::MAX_INDIVIDUAL_REWARD_RATIO;
        $finalReward = min($baseReward, $maxReward);

        if ($finalReward < $baseReward) {
            $bonusesApplied[] = 'capped';
        }

        return [
            'resource' => $raid->reward_resource,
            'amount' => (int) $finalReward,
            'bonuses_applied' => $bonusesApplied,
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

}
