<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use OpenDominion\Factories\DominionFactory;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\SelectorService;
use RuntimeException;

class RoundController extends AbstractController
{
    /** @var DominionFactory */
    protected $dominionFactory;

    /**
     * RoundController constructor.
     *
     * @param DominionFactory $dominionFactory
     */
    public function __construct(DominionFactory $dominionFactory)
    {
        $this->dominionFactory = $dominionFactory;
    }

    public function getRegister(Round $round)
    {
        $this->guardAgainstUserAlreadyHavingDominionInRound($round);

        return view('pages.round.register', [
            'raceHelper' => app(RaceHelper::class),
            'round' => $round,
            'races' => Race::all(),
        ]);
    }

    public function postRegister(Request $request, Round $round)
    {
        $this->guardAgainstUserAlreadyHavingDominionInRound($round);

        $this->validate($request, [
            'dominion_name' => 'required|string|max:50',
            'ruler_name' => 'string|max:50',
            'race' => 'required|exists:races,id',
            'realm' => 'in:random',
        ]);

        $dominion = $this->dominionFactory->create(
            Auth::user(),
            $round,
            Race::find($request->get('race')),
            $request->get('realm'),
            $request->get('ruler_name', Auth::user()->display_name),
            $request->get('dominion_name')
        );

        if ($round->isActive()) {
            $dominionSelectorService = app(SelectorService::class);
            $dominionSelectorService->selectUserDominion($dominion);
        }

        // todo: fire laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsEvent(
            'round',
            'register',
            (string)$round->number
        ));

        $request->session()->flash('alert-success',
            "You have successfully registered to round {$round->number} ({$round->league->description}).");

        return redirect()->route('dominion.status');
    }

    protected function guardAgainstUserAlreadyHavingDominionInRound(Round $round)
    {
        $dominions = Dominion::where([
            'user_id' => Auth::user()->id,
            'round_id' => $round->id,
        ])->get();

        if (!$dominions->isEmpty()) {
            throw new RuntimeException("User already has a dominion in round {$round->number}");
        }
    }
}
