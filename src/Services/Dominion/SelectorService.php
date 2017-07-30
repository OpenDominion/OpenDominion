<?php

namespace OpenDominion\Services\Dominion;

use Auth;
use Exception;
use OpenDominion\Contracts\Services\Dominion\SelectorService as SelectorServiceContract;
use OpenDominion\Models\Dominion;
use OpenDominion\Repositories\DominionRepository;
use Session;

class SelectorService implements SelectorServiceContract
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
     * {@inheritdoc}
     */
    public function hasUserSelectedDominion()
    {
        return (session(self::SESSION_NAME) !== null);
    }

    /**
     * {@inheritdoc}
     */
    public function selectUserDominion(Dominion $dominion)
    {
        $user = Auth::user();

        // Check if Dominion belongs to logged in user
        if ($dominion->user_id != $user->id) {
            throw new Exception('User cannot select someone else\'s Dominion');
        }

        // Check that round is active
        if (!$dominion->round->hasStarted()) {
            throw new Exception('Cannot select a dominion when the round hasn\'t started yet');
        }

        // todo: fire laravel event
//        event(new Dominion\SelectedEvent($user, $dominion));

        session([self::SESSION_NAME => $dominion->id]);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function unsetUserSelectedDominion()
    {
        Session::forget(self::SESSION_NAME);
    }
}
