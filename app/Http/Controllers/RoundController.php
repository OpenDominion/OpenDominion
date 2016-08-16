<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use OpenDominion\Models\Round;
use OpenDominion\Repositories\DominionRepository;
use OpenDominion\Repositories\RaceRepository;
use Validator;

class RoundController extends AbstractController
{
    /** @var DominionRepository */
    protected $dominions;

    /** @var RaceRepository */
    protected $races;

    public function __construct(DominionRepository $dominions, RaceRepository $races)
    {
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

        // todo: validate $request

        $this->validate($request, [
            'dominion_name' => 'required',
            'race' => 'required|integer',
            'realm' => 'in:random',
        ]);

        $dominion = $this->dominions->create([
            'user_id' => Auth::user()->id,
            'round_id' => $round->id,
            'realm_id' => 0, // todo
            'race_id' => $request->get('race'),
            'name' => $request->get('dominion_name'),
        ]);

        $request->session()->flash('alert-success',
            "You have successfully registered to round {$round->number} ({$round->league->description} League)");

        return redirect('dashboard');
    }

    protected function checkIfUserAlreadyHasDominionInThisRound(Round $round)
    {
        $dominions = $this->dominions->findWhere([
            'user_id' => Auth::user()->id,
            'round_id' => $round->id,
        ], ['id']);

        if (!$dominions->isEmpty()) {
            throw new \Exception("User already has a dominion in round {$round->number}");
        }
    }
}
