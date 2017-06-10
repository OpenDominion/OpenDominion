<?php

namespace OpenDominion\Http\Controllers;

use OpenDominion\Repositories\Criteria\Dominion\DominionFromCurrentLoggedInUserCriteria;
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
        $this->dominions->pushCriteria(DominionFromCurrentLoggedInUserCriteria::class);
        $dominions = $this->dominions->with(['round', 'realm', 'race'])->all();

        $rounds = $this->rounds->with('league')->all();

        return view('pages.dashboard', [
            'dominions' => $dominions,
            'rounds' => $rounds,
        ]);
    }
}
