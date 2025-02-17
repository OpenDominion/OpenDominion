<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use DB;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use LogicException;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Factories\DominionFactory;
use OpenDominion\Factories\RealmFactory;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
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

        // todo: make this its own FormRequest class? Might be hard due to depending on $round->pack_size, needs investigating
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->validate($request, [
            'dominion_name' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/[a-zA-Z0-9]{3,}/i',
                Rule::unique('dominions', 'name')->where(function ($query) use ($round) {
                    return $query->where('round_id', $round->id);
                }),
            ],
            'ruler_name' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('dominions', 'ruler_name')->where(function ($query) use ($round) {
                    return $query->where('round_id', $round->id);
                }),
            ],
            'race' => 'required|exists:races,key',
            'realm_type' => 'in:random,join_pack,create_pack',
            'pack_name' => ('string|min:3|max:50|' . ($request->get('realm_type') !== 'random' ? 'required_if:realm,join_pack,create_pack' : 'nullable')),
            'pack_password' => ('string|min:3|max:50|' . ($request->get('realm_type') !== 'random' ? 'required_if:realm,join_pack,create_pack' : 'nullable')),
            'pack_size' => "integer|min:2|max:{$round->pack_size}|required_if:realm,create_pack",
        ]);

        /** @var Realm $realm */
        $realm = null;

        /** @var Dominion $dominion */
        $dominion = null;

        /** @var string $dominionName */
        $dominionName = null;

        try {
            DB::transaction(function () use ($request, $round, &$realm, &$dominion, &$dominionName) {
                $realmFinderService = app(RealmFinderService::class);
                $realmFactory = app(RealmFactory::class);

                /** @var User $user */
                $user = Auth::user();
                $race = Race::where('key', $request->get('race'))->firstOrFail();
                $pack = null;

                if (!$race->playable) {
                    throw new GameException('Invalid race selection');
                }

                switch ($request->get('realm_type')) {
                    case 'random':
                        $realm = $realmFinderService->findRealm($round, $race, $user);
                        break;

                    case 'join_pack':
                        $pack = $this->packService->getPack(
                            $round,
                            $request->get('pack_name'),
                            $request->get('pack_password'),
                            $race
                        );

                        $realm = $pack->realm;
                        break;

                    case 'create_pack':
                        if (!$round->packRegistrationOpen()) {
                            throw new GameException('Pack registration is currently closed');
                        }
                        $realm = $realmFinderService->findRealm(
                            $round,
                            $race,
                            $user,
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

                $dominion = $this->dominionFactory->create(
                    $user,
                    $realm,
                    $race,
                    ($request->get('ruler_name') ?: $user->display_name),
                    $dominionName,
                    $pack
                );

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
            });
        } catch (QueryException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $dominionSelectorService = app(SelectorService::class);
        $dominionSelectorService->selectUserDominion($dominion);

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
