<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use OpenDominion\Factories\DominionFactory;
use OpenDominion\Factories\PackFactory;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Pack;
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

    /** @var PackFactory */
    protected $packFactory;

    /**
     * RoundController constructor.
     *
     * @param DominionFactory $dominionFactory
     */
    public function __construct(DominionFactory $dominionFactory, PackFactory $packFactory)
    {
        $this->dominionFactory = $dominionFactory;
        $this->packFactory = $packFactory;
    }

    public function getRegister(Round $round)
    {
        $this->guardAgainstUserAlreadyHavingDominionInRound($round);

        return view('pages.round.register', [
            'round' => $round,
            'races' => Race::all(),
        ]);
    }

    public function postRegister(Request $request, Round $round)
    {
        $this->guardAgainstUserAlreadyHavingDominionInRound($round);

        $this->validate($request, [
            'dominion_name' => 'required',
            'race' => 'required|integer',
            'realm' => 'in:random,pack',
        ]);
        $realmType = $request->get('realm');
        // Validate pack things
        $race = Race::find($request->get('race'));

        $pack = null;
        if($realmType === 'pack')
        {
            $pack = $this->validatePack($request, $round, $race);
        }

        $dominion = $this->dominionFactory->create(
            Auth::user(),
            $round,
            $race,
            $realmType,
            $request->get('dominion_name'),
            $pack
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

    protected function validatePack(Request $request, Round $round, Race $race): Pack
    {
        // TODO: Handle validation errors gracefully...
        if(!$request->filled('pack_password')) {
            return null;
        }

        $password = $request->get('pack_password');

        if($request->has('create_pack'))
        {
            $packSize = $request->get('pack_size');

            if($packSize < 2 || $packSize > 6)
            {
                throw new RuntimeException("Pack size must be between 2 and 6.");
            }

            $pack = $this->packFactory->create($round, Auth::user(), $password, $packSize);
        }
        else {
            $packs = Pack::where([
                'password' => $password,
                'round_id' => $round->id
            ])->withCount('dominions')->get();
    
            if($packs->isEmpty()) {
                throw new RuntimeException("No pack with that password found in round {$round->number}");
            }
    
            $pack = $packs[0];

            // TODO: race condition here
            // TODO: Pack size should be a setting?
            if($pack->dominions_count == 6) {
                throw new RuntimeException("Pack is already full");
            }

            if($pack->realm->alignment !== $race->alignment){
                throw new RuntimeException("Race has wrong aligment to rest of pack.");
            }
        }

        return $pack;
    }
}
