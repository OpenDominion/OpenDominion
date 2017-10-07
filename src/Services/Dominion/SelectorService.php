<?php

namespace OpenDominion\Services\Dominion;

use Auth;
use OpenDominion\Models\Dominion;
use OpenDominion\Repositories\DominionRepository;
use RuntimeException;
use Session;

class SelectorService
{
    const SESSION_NAME = 'selected_dominion_id';

    /** @var DominionRepository */
    protected $dominions;

    /** @var Dominion */
    protected $selectedDominion;

    public function __construct(DominionRepository $dominions)
    {
        $this->dominions = $dominions;
    }

    /**
     * Returns whether the current logged in user has selected a dominion.
     *
     * @return bool
     */
    public function hasUserSelectedDominion()
    {
        return (session(self::SESSION_NAME) !== null);
    }

    /**
     * Selects a dominion for the logged in user.
     *
     * @param Dominion $dominion
     * @throws RuntimeException
     */
    public function selectUserDominion(Dominion $dominion)
    {
        $user = Auth::user();

        // Check if Dominion belongs to logged in user
        if ($dominion->user_id != $user->id) {
            throw new RuntimeException('User cannot select someone else\'s Dominion');
        }

        // Check that round is active
        if (!$dominion->round->hasStarted()) {
            throw new RuntimeException('Cannot select a dominion when the round hasn\'t started yet');
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
    public function getUserSelectedDominion()
    {
        $dominionId = session(self::SESSION_NAME);

        if ($dominionId === null) {
            return null;
        }

        if ($this->selectedDominion === null || ($dominionId !== $this->selectedDominion->id)) {
            $this->selectedDominion = $this->dominions->with(['realm', 'race.perks', 'race.perks.type', 'race', 'race.units'])->find($dominionId);
        }

        return $this->selectedDominion;
    }

    /**
     * Unsets the selected dominion for the logged in user.
     *
     * @return void
     */
    public function unsetUserSelectedDominion()
    {
        Session::forget(self::SESSION_NAME);
    }
}
