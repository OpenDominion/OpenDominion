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

        // todo: fire laravel event
//        event(new Dominion\SelectedEvent($user, $dominion));

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
            $this->selectedDominion = Dominion::with([
                'race',
                'race.perks',
                'race.units',
                'race.units.perks',
                'realm',
            ])->findOrFail($dominionId);
        }

        return $this->selectedDominion;
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

        if ($activeDominions->count() !== 1) {
            // todo: try select dominion which is about to start?
            return null;
        }

        $dominion = $activeDominions->first();

        $this->selectUserDominion($dominion);

        return $dominion;
    }
}
