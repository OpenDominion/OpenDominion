<?php

namespace OpenDominion\Services\Dominion;

use Auth;
use Illuminate\Database\Eloquent\Collection;
use LogicException;
use OpenDominion\Models\Dominion;
use RuntimeException;
use Session;

class SelectorService
{
    public const SESSION_NAME = 'selected_dominion_id';

    /** @var Dominion */
    protected $selectedDominion;

    /**
     * Returns whether the current logged in user has selected a dominion.
     *
     * @return bool
     */
    public function hasUserSelectedDominion(): bool
    {
        return (session(self::SESSION_NAME) !== null);
    }

    /**
     * Selects a dominion for the logged in user.
     *
     * @param Dominion $dominion
     * @throws LogicException
     * @throws RuntimeException
     */
    public function selectUserDominion(Dominion $dominion): void
    {
        $user = Auth::user();

        if (!$user) {
            throw new LogicException('Cannot select user dominion when not logged in');
        }

        // Check if Dominion belongs to logged in user
        if ($dominion->user_id != $user->id) {
            throw new RuntimeException('User cannot select someone else\'s Dominion');
        }

        session([self::SESSION_NAME => $dominion->id]);
    }

    /**
     * Returns the selected dominion for the logged in user, or null if user
     * hasn't selected any.
     *
     * @return Dominion|null
     */
    public function getUserSelectedDominion(): ?Dominion
    {
        $dominionId = session(self::SESSION_NAME);

        if ($dominionId === null) {
            return null;
        }

        if ($this->selectedDominion === null || ($dominionId !== $this->selectedDominion->id)) {
            $this->selectedDominion = Dominion::withGameRelations()->findOrFail($dominionId);
        }

        if ($this->selectedDominion->round->isActive()) {
            $index = (int) $this->selectedDominion->round->getTick();
            $activity = $this->selectedDominion->hourly_activity;
            if (!$activity || (isset($activity[$index]) && $activity[$index] === '0')) {
                app(RoundMutationService::class)->runForDominion($this->selectedDominion, function (Dominion $dominion): void {
                    $this->recordHourlyActivity($dominion);
                }, false);
            }
        }

        return $this->selectedDominion;
    }

    /**
     * Reuse the model already refreshed under the mutation transaction's locks.
     */
    public function useLockedDominion(Dominion $dominion): void
    {
        if ((int) session(self::SESSION_NAME) !== $dominion->id) {
            throw new LogicException('Locked dominion does not match the current selection.');
        }

        $this->selectedDominion = $dominion;
        $this->recordHourlyActivity($dominion);
    }

    protected function recordHourlyActivity(Dominion $dominion): void
    {
        if (!$dominion->round->isActive()) {
            return;
        }

        $index = (int) $dominion->round->getTick();
        $activity = $dominion->hourly_activity ?: str_repeat('0', 47 * 24);
        if ($index >= 0 && $index < strlen($activity) && $activity[$index] === '0') {
            $activity[$index] = '1';
            $dominion->hourly_activity = $activity;
            $dominion->saveQuietly();
        }
    }

    public function forgetSelectedDominion(): void
    {
        $this->selectedDominion = null;
    }

    /**
     * Unsets the selected dominion for the logged in user.
     */
    public function unsetUserSelectedDominion(): void
    {
        Session::forget(self::SESSION_NAME);
    }

    /**
     * Tries to auto-select a dominion for the logged in user.
     *
     * Auto-select only works when the user currently has only one active dominion.
     *
     * @return Dominion|null The auto-selected dominion
     * @throws LogicException
     * @throws RuntimeException
     */
    public function tryAutoSelectDominionForAuthUser(): ?Dominion
    {
        if ($this->hasUserSelectedDominion()) {
            return $this->getUserSelectedDominion();
        }

        $user = Auth::user();

        if (!$user) {
            throw new LogicException('Cannot auto-select user dominion when not logged in');
        }

        /** @var Collection $activeDominions */
        $activeDominions = $user->dominions()->active()->get();

        if ($activeDominions->count() == 0) {
            // Rounds that haven't started yet
            $activeDominions = Dominion::with('round')
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->get()
                ->filter(function ($dominion) {
                    if ($dominion->round->start_date > now()) {
                        return $dominion;
                    }
                });
        }

        if ($activeDominions->count() == 0) {
            return null;
        }

        $dominion = $activeDominions->first();

        $this->selectUserDominion($dominion);

        return $dominion;
    }
}
