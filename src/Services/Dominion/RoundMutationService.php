<?php

namespace OpenDominion\Services\Dominion;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundTickRun;

class RoundMutationService
{
    /**
     * Shared round locks allow player actions to run concurrently while excluding hourly ticks.
     * Always acquire this lock before reading state used to validate or perform a mutation.
     */
    public function run(Round|int $round, callable $callback, bool $checkCurrent = true): mixed
    {
        $roundId = $round instanceof Round ? $round->id : $round;

        return (new Round())->getConnection()->transaction(function () use ($roundId, $callback, $checkCurrent) {
            $lockedRound = Round::query()->whereKey($roundId)->sharedLock()->firstOrFail();
            if ($checkCurrent) {
                $this->assertRoundCurrent($lockedRound);
            }

            return $callback($lockedRound);
        });
    }

    /**
     * Refreshes the caller's instance after locking so validation never uses a stale snapshot.
     */
    public function runForDominion(Dominion $dominion, callable $callback, bool $checkCurrent = true, bool $loadGameRelations = false): mixed
    {
        return $this->run((int) $dominion->round_id, function (Round $round) use ($dominion, $callback, $loadGameRelations) {
            $query = Dominion::query()->whereKey($dominion->id)->lockForUpdate();
            if ($loadGameRelations) {
                $query->with(Dominion::query()->withGameRelations()->getEagerLoads())->withCachedRace();
            }
            $freshDominion = $query->firstOrFail();
            $dominion->setRawAttributes($freshDominion->getAttributes(), true);
            $dominion->setRelations($freshDominion->getRelations());
            $dominion->setRelation('round', $round);

            return $callback($dominion);
        }, $checkCurrent);
    }

    /**
     * Failed or overdue hourly ticks must be recovered before ordinary gameplay continues.
     * A round with no tick ledger yet remains available during deployment/bootstrap.
     */
    public function assertRoundCurrent(Round|int $round): void
    {
        $round = $round instanceof Round ? $round : Round::query()->findOrFail($round);
        if (!$round->isActive()) {
            return;
        }

        $hour = now()->startOfHour();
        $runs = RoundTickRun::query()->where('round_id', $round->id)->where('tick_at', '<=', $hour);
        if ((clone $runs)->whereNull('completed_at')->exists()) {
            throw new GameException('The Emperor is currently collecting taxes and cannot fulfill your request. Please try again.');
        }

        $latestCompletedHour = (clone $runs)->whereNotNull('completed_at')->max('tick_at');
        if ($latestCompletedHour !== null && $latestCompletedHour < $hour->toDateTimeString()) {
            throw new GameException('The Emperor is currently collecting taxes and cannot fulfill your request. Please try again.');
        }
    }
}
