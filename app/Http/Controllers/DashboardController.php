<?php

namespace OpenDominion\Http\Controllers;

use OpenDominion\Repositories\Criteria\Dominion\DominionFromCurrentLoggedInUserCriteria;
use OpenDominion\Repositories\Criteria\Round\RoundHasntEndedCriteria;
use OpenDominion\Repositories\DominionRepositoriy;
use OpenDominion\Repositories\RoundRepository;

class DashboardController extends AbstractController
{
    /** @var DominionRepositoriy */
    protected $dominions;

    /** @var RoundRepository */
    protected $rounds;

    public function __construct(DominionRepositoriy $dominions, RoundRepository $rounds)
    {
        $this->dominions = $dominions;
        $this->rounds = $rounds;
    }

    public function getIndex()
    {
        $this->dominions->pushCriteria(DominionFromCurrentLoggedInUserCriteria::class);
        $dominions = $this->dominions->all();

        $this->rounds->pushCriteria(RoundHasntEndedCriteria::class);
        $rounds = $this->rounds->with('league')->all();

        return view('pages.dashboard', [
            'dominions' => $dominions,
            'rounds' => $rounds,
        ]);
    }
}
