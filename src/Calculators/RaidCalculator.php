<?php

namespace OpenDominion\Calculators;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Raid;
use OpenDominion\Models\RaidContribution;
use OpenDominion\Models\RaidObjective;
use OpenDominion\Models\Realm;

class RaidCalculator
{
    /** @var LandCalculator */
    protected $landCalculator;

    public function __construct()
    {
        $this->landCalculator = app(LandCalculator::class);
    }

    /**
     * Get the mana cost for a tactic spell option.
     */
    public function getTacticManaCost(Dominion $dominion, array $spellOption): int
    {
        $manaCostMultiplier = $spellOption['mana_cost'] ?? 0;
        $totalLand = $this->landCalculator->getTotalLand($dominion);
        return rceil($manaCostMultiplier * $totalLand);
    }

    /**
     * Get the total score for a raid objective.
     */
    public function getObjectiveScore(RaidObjective $objective): int
    {
        return RaidContribution::where('raid_objective_id', $objective->id)->sum('score');
    }

    /**
     * Get the progress percentage for a raid objective.
     */
    public function getObjectiveProgress(RaidObjective $objective): float
    {
        $currentScore = $this->getObjectiveScore($objective);
        $requiredScore = $objective->score_required;

        if ($requiredScore <= 0) {
            return 0;
        }

        return min(100, ($currentScore / $requiredScore) * 100);
    }

    /**
     * Check if a raid objective is completed.
     */
    public function isObjectiveCompleted(RaidObjective $objective): bool
    {
        return $this->getObjectiveScore($objective) >= $objective->score_required;
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
}
