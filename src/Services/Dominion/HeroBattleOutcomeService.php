<?php

namespace OpenDominion\Services\Dominion;

use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Calculators\RaidCalculator;
use OpenDominion\Models\HeroBattle;
use OpenDominion\Models\HeroCombatant;
use OpenDominion\Models\RaidContribution;
use OpenDominion\Services\NotificationService;

/**
 * Applies the consequences of a finished hero battle: records, tournament standings,
 * ratings, raid contributions and notifications. Works per team, so every hero on the
 * winning side shares the win.
 */
class HeroBattleOutcomeService
{
    public function __construct(
        protected HeroCalculator $heroCalculator,
        protected RaidCalculator $raidCalculator,
        protected NotificationService $notificationService,
    ) {
    }

    public function finalize(HeroBattle $heroBattle): void
    {
        $heroBattle->loadMissing('combatants.hero.dominion', 'tournaments.participants');

        $this->recordResults($heroBattle);

        if ($heroBattle->pvp) {
            $this->updateRatings($heroBattle);
            $this->notify($heroBattle);
        }

        if ($heroBattle->raid_tactic_id !== null && $heroBattle->winning_team !== null) {
            $this->recordRaidContributions($heroBattle);
        }
    }

    protected function recordResults(HeroBattle $heroBattle): void
    {
        $tournament = $heroBattle->tournaments->first();

        foreach ($heroBattle->combatants->whereNotNull('hero_id') as $combatant) {
            $result = $this->resultFor($heroBattle, $combatant);
            $participant = $tournament?->participants->where('hero_id', $combatant->hero_id)->first();

            if ($heroBattle->pvp) {
                $combatant->hero->increment("stat_combat_{$result}");
            }
            $participant?->increment($result);
        }
    }

    /**
     * @return string one of 'wins', 'losses', 'draws'
     */
    protected function resultFor(HeroBattle $heroBattle, HeroCombatant $combatant): string
    {
        if ($heroBattle->isDraw()) {
            return 'draws';
        }

        return $heroBattle->isWinner($combatant) ? 'wins' : 'losses';
    }

    /**
     * Each hero is rated against the average rating of the heroes on other teams.
     */
    protected function updateRatings(HeroBattle $heroBattle): void
    {
        $heroes = $heroBattle->combatants->whereNotNull('hero_id');
        $teamCount = $heroes->pluck('team')->unique()->count();
        if ($teamCount < 2) {
            return;
        }

        $ratings = $heroes->mapWithKeys(fn (HeroCombatant $c) => [$c->id => $c->hero->combat_rating]);

        foreach ($heroes as $combatant) {
            $opponentRating = $heroes->where('team', '!=', $combatant->team)
                ->map(fn (HeroCombatant $c) => $ratings[$c->id])
                ->average();

            $score = match ($this->resultFor($heroBattle, $combatant)) {
                'wins' => 1,
                'losses' => 0,
                'draws' => 1 / $teamCount,
            };

            $combatant->hero->combat_rating = $this->heroCalculator->calculateRatingChange(
                $ratings[$combatant->id],
                $opponentRating,
                $score,
            );
            $combatant->hero->save();
        }
    }

    protected function recordRaidContributions(HeroBattle $heroBattle): void
    {
        $tactic = $heroBattle->tactic;

        foreach ($heroBattle->winningCombatants()->whereNotNull('hero_id') as $combatant) {
            $dominion = $combatant->hero->dominion;

            $dominion->stat_raid_score += $this->raidCalculator->getTacticPointsEarned($dominion, $tactic);
            $dominion->save(['event' => HistoryService::EVENT_ACTION_RAID_ACTION]);

            RaidContribution::create([
                'realm_id' => $dominion->realm_id,
                'dominion_id' => $dominion->id,
                'raid_objective_id' => $tactic->raid_objective_id,
                'raid_tactic_id' => $tactic->id,
                'type' => $tactic->type,
                'score' => $this->raidCalculator->getTacticScore($dominion, $tactic),
            ]);
        }
    }

    protected function notify(HeroBattle $heroBattle): void
    {
        foreach ($heroBattle->combatants->whereNotNull('hero_id') as $combatant) {
            $this->notificationService->queueNotification('hero_battle', ['status' => 'ended']);
            $this->notificationService->sendNotifications($combatant->hero->dominion, 'irregular_dominion');
        }
    }
}
