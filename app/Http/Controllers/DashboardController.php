<?php

namespace OpenDominion\Http\Controllers;

use OpenDominion\Repositories\Criteria\Dominion\FromCurrentLoggedInUser;
use OpenDominion\Repositories\Criteria\Round\HasntEnded;
use OpenDominion\Repositories\DominionRepository;
use OpenDominion\Repositories\RoundRepository;

class DashboardController extends AbstractController
{
    /** @var DominionRepository */
    protected $dominions;

    /** @var RoundRepository */
    protected $rounds;

    public function __construct(DominionRepository $dominions, RoundRepository $rounds)
    {
        $this->dominions = $dominions;
        $this->rounds = $rounds;
    }

    public function getIndex()
    {
        $this->dominions->pushCriteria(FromCurrentLoggedInUser::class);
        $dominions = $this->dominions->all();

        $this->rounds->pushCriteria(HasntEnded::class);
        $rounds = $this->rounds->with('league')->all();

        return view('pages.dashboard', [
            'dominions' => $dominions,
            'rounds' => $rounds,
        ]);
    }
}
