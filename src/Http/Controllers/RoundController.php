<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use DB;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use LogicException;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Factories\DominionFactory;
use OpenDominion\Factories\RealmFactory;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\SelectorService;
use OpenDominion\Services\PackService;
use OpenDominion\Services\RealmFinderService;

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
     * @param PackService $packService
     */
    public function __construct(DominionFactory $dominionFactory, PackService $packService)
    {
        $this->dominionFactory = $dominionFactory;
        $this->packService = $packService;
    }

    public function getRegister(Round $round)
    {
        try {
            $this->guardAgainstUserAlreadyHavingDominionInRound($round);
        } catch (GameException $e) {
            return redirect()
                ->route('dashboard')
                ->withErrors([$e->getMessage()]);
        }

        $races = Race::query()
            ->with(['perks'])
            ->orderBy('name')
            ->get();

        return view('pages.round.register', [
            'raceHelper' => app(RaceHelper::class),
            'round' => $round,
            'races' => $races,
        ]);
    }

    public function postRegister(Request $request, Round $round)
    {
        try {
            $this->guardAgainstUserAlreadyHavingDominionInRound($round);
        } catch (GameException $e) {
            return redirect()
                ->route('dashboard')
                ->withErrors([$e->getMessage()]);
        }

        // todo: make this its own FormRequest class
        $this->validate($request, [
            'dominion_name' => 'required|string|max:50',
            'ruler_name' => 'nullable|string|max:50',
            'race' => 'required|exists:races,id',
            'realm_type' => 'in:random,join_pack,create_pack',
            'pack_name' => ('string|min:3|max:50|' . ($request->get('realm_type') !== 'random' ? 'required_if:realm,join_pack,create_pack' : 'nullable')),
            'pack_password' => ('string|min:3|max:50|' . ($request->get('realm_type') !== 'random' ? 'required_if:realm,join_pack,create_pack' : 'nullable')),
            'pack_size' => "integer|min:2|max:{$round->pack_size}|required_if:realm,create_pack",
        ]);

        $realmFinderService = app(RealmFinderService::class);
        $realmFactory = app(RealmFactory::class);

        DB::beginTransaction();

        /** @var User $user */
        $user = Auth::user();
        $race = Race::findOrFail($request->get('race'));
        $pack = null;

        switch ($request->get('realm_type')) {
            case 'random':
                $realm = $realmFinderService->findRandomRealm($round, $race);
                break;

            case 'join_pack':
                $pack = $this->packService->getPack(
                    $round,
                    $race->alignment,
                    $request->get('pack_name'),
                    $request->get('pack_password')
                );

                if (!$pack) {
                    return redirect()->back()
                        ->withInput($request->all())
                        ->withErrors(['The pack you specified was not found.']);
                }

                $realm = $pack->realm;
                break;

            case 'create_pack':
                $realm = $realmFinderService->findRandomRealm(
                    $round,
                    $race,
                    $request->get('pack_size'),
                    true
                );
                break;

            default:
                throw new LogicException('Unsupported realm type');
        }

        if (!$realm) {
            $realm = $realmFactory->create($round, $race->alignment);
        }

        $dominionName = $request->get('dominion_name');

        try {
            $dominion = $this->dominionFactory->create(
                $user,
                $realm,
                $race,
                ($request->get('ruler_name') ?: Auth::user()->display_name),
                $dominionName,
                $pack
            );
        } catch (QueryException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors(["Someone already registered a dominion with the name '{$dominionName}' for this round."]);
        }

        if ($request->get('realm_type') === 'create_pack') {
            $pack = $this->packService->createPack(
                $dominion,
                $request->get('pack_name'),
                $request->get('pack_password'),
                $request->get('pack_size')
            );

            $dominion->pack_id = $pack->id;
            $dominion->save();

            $pack->realm_id = $realm->id;
            $pack->save();
        }

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

        $request->session()->flash(
            'alert-success',
            ("You have successfully registered to round {$round->number} ({$round->league->description}). You have been placed in realm {$realm->number} ({$realm->name}) with " . ($realm->dominions()->count() - 1) . ' other dominion(s).')
        );

        return redirect()->route('dominion.status');
    }

    /**
     * Throws exception if logged in user already has a dominion a round.
     *
     * @param Round $round
     * @throws GameException
     */
    protected function guardAgainstUserAlreadyHavingDominionInRound(Round $round): void
    {
        // todo: make this a route middleware instead

        $dominions = Dominion::where([
            'user_id' => Auth::user()->id,
            'round_id' => $round->id,
        ])->get();

        if (!$dominions->isEmpty()) {
            throw new GameException("You already have a dominion in round {$round->number}");
        }
    }
}
