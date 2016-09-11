<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use Exception;
use Illuminate\Http\Request;
use OpenDominion\Models\Round;
use OpenDominion\Repositories\DominionRepository;
use OpenDominion\Repositories\RaceRepository;
use OpenDominion\Services\DominionService;

class RoundController extends AbstractController
{
    /** @var DominionService */
    protected $dominionService;

    /** @var DominionRepository */
    protected $dominions;

    /** @var RaceRepository */
    protected $races;

    public function __construct(DominionService $dominionService, DominionRepository $dominions, RaceRepository $races)
    {
        $this->dominionService = $dominionService;
        $this->dominions = $dominions;
        $this->races = $races;
    }

    public function getRegister(Round $round)
    {
        $this->checkIfUserAlreadyHasDominionInThisRound($round);

        return view('pages.round.register', [
            'round' => $round,
            'races' => $this->races->all(),
        ]);
    }

    public function postRegister(Request $request, Round $round)
    {
        $this->checkIfUserAlreadyHasDominionInThisRound($round);

        $this->validate($request, [
            'dominion_name' => 'required',
            'race' => 'required|integer',
            'realm' => 'in:random',
        ]);

        $this->dominionService->create(
            Auth::user(),
            $round,
            $this->races->find($request->get('race')),
            $request->get('realm'),
            $request->get('dominion_name')
        );

        $request->session()->flash('alert-success',
            "You have successfully registered to round {$round->number} ({$round->league->description})");

        return redirect('dashboard');
    }

    protected function checkIfUserAlreadyHasDominionInThisRound(Round $round)
    {
        $dominions = $this->dominions->findWhere([
            'user_id' => Auth::user()->id,
            'round_id' => $round->id,
        ], ['id']);

        if (!$dominions->isEmpty()) {
            throw new Exception("User already has a dominion in round {$round->number}");
        }
    }
}
