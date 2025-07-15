<?php

namespace OpenDominion\Calculators;

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
     * Get recent contributions for a raid objective.
     */
    public function getRecentContributions(RaidObjective $objective, int $limit = 10): array
    {
        return RaidContribution::where('raid_objective_id', $objective->id)
            ->with(['dominion', 'dominion.realm'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($contribution) {
                return [
                    'dominion_name' => $contribution->dominion->name,
                    'realm_name' => $contribution->dominion->realm->name,
                    'type' => $contribution->type,
                    'score' => $contribution->score,
                    'created_at' => $contribution->created_at,
                ];
            })
            ->toArray();
    }

    /**
     * Get recent contributions for a raid objective from a specific realm.
     */
    public function getRecentContributionsInRealm(RaidObjective $objective, Realm $realm, int $limit = 10): array
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
}
