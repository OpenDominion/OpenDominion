<?php

namespace OpenDominion\Services;

use Auth;
use OpenDominion\Models\Dominion;
use OpenDominion\Repositories\DominionRepository;

class DominionSelectorService
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
     * @return bool
     */
    public function hasUserSelectedDominion()
    {
        return (session('selected_dominion_id') !== null);
    }

    /**
     * @param Dominion $dominion
     * @throws \Exception
     */
    public function selectUserDominion(Dominion $dominion)
    {
        if ($dominion->user_id != Auth::user()->id) {
            throw new \Exception('User cannot select someone else\'s Dominion');
        }

        session([self::SESSION_NAME => $dominion->id]);
    }

    /**
     * @return Dominion|null
     */
    public function getUserSelectedDominion()
    {
        $dominionId = session(self::SESSION_NAME);

        if ($dominionId === null) {
            return null;
        }

        if ($this->selectedDominion === null || ($dominionId !== $this->selectedDominion->id)) {
            $this->selectedDominion = $this->dominions->find($dominionId);
        }

        return $this->selectedDominion;
    }
}
