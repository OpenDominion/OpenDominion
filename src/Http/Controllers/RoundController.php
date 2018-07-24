<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use DB;
use Illuminate\Http\Request;
use OpenDominion\Factories\DominionFactory;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\SelectorService;
use OpenDominion\Services\PackService;
use RuntimeException;

class RoundController extends AbstractController
{
    /** @var DominionFactory */
    protected $dominionFactory;

    /** @var PackService */
    protected $packService;

    /**
     * RoundController constructor.
     *
     * @param DominionFactory $dominionFactory
     */
    public function __construct(DominionFactory $dominionFactory, PackService $packService)
    {
        $this->dominionFactory = $dominionFactory;
        $this->packService = $packService;
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
            'ruler_name' => 'nullable|string|max:50',
            'race' => 'required|exists:races,id',
            'realm' => 'in:random,pack',
        ]);

        $realmType = $request->get('realm');
        $race = Race::find($request->get('race'));

        DB::beginTransaction();

        $pack = null;
        if($realmType === 'pack')
        {
            $pack = $this->packService->getOrCreatePack($request, $round, $race);
        }

        $dominion = $this->dominionFactory->create(
            Auth::user(),
            $round,
            $race,
            $realmType,
            ($request->get('ruler_name') ?: Auth::user()->display_name),
            $request->get('dominion_name'),
            $pack
        );

        DB::commit();

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
