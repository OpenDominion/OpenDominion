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
                $this->assertRoundCurrent($roundId);
            }

            return $callback($lockedRound);
        });
    }

    /**
     * Refreshes the caller's instance after locking so validation never uses a stale snapshot.
     */
    public function runForDominion(Dominion $dominion, callable $callback, bool $checkCurrent = true): mixed
    {
        return $this->run((int) $dominion->round_id, function (Round $round) use ($dominion, $callback, $checkCurrent) {
            $freshDominion = Dominion::query()->whereKey($dominion->id)->lockForUpdate()->firstOrFail();
            $dominion->setRawAttributes($freshDominion->getAttributes(), true);
            $dominion->unsetRelations();
            $dominion->setRelation('round', $round);

            if ($checkCurrent) {
                $this->assertRoundCurrent($round->id);
            }

            return $callback($dominion);
        }, false);
    }

    /**
     * Failed or overdue hourly ticks must be recovered before ordinary gameplay continues.
     * A round with no tick ledger yet remains available during deployment/bootstrap.
     */
    public function assertRoundCurrent(int $roundId): void
    {
        $round = Round::query()->findOrFail($roundId);
        if (!$round->isActive()) {
            return;
        }

        $hour = now()->startOfHour();
        $runs = RoundTickRun::query()->where('round_id', $roundId)->where('tick_at', '<=', $hour);
        if ((clone $runs)->whereNull('completed_at')->exists()) {
            throw new GameException('The Emperor is currently collecting taxes and cannot fulfill your request. Please try again.');
        }

        $latestCompletedHour = (clone $runs)->whereNotNull('completed_at')->max('tick_at');
        if ($latestCompletedHour !== null && $latestCompletedHour < $hour->toDateTimeString()) {
            throw new GameException('The Emperor is currently collecting taxes and cannot fulfill your request. Please try again.');
        }
    }
}
