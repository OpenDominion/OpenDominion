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
use OpenDominion\Services\RealmAssignmentService;

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

        $hasDiscord = true;
        $user = Auth::user();
        $roundsPlayed = $user->dominions()->count();
        $endorsements = $user->endorsements()->get();
        $positive = $user->endorsements()->where('endorsed', true)->count();
        $negative = $user->endorsements()->where('endorsed', false)->count();
        if ($negative > (2 * $positive)) {
            $hasDiscord = false;
        }
        if ($user->discordUser === null && $user->dominions()->count() > 0) {
            $hasDiscord = false;
        }

        $races = Race::query()
            ->with(['perks'])
            ->active()
            ->orderBy('name')
            ->get();

        return view('pages.round.register', [
            'raceHelper' => app(RaceHelper::class),
            'round' => $round,
            'races' => $races,
            'hasDiscord' => $hasDiscord,
            'isLateStart' => $round->hasStarted(),
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

        // Enforce quick protection for late starts
        if ($round->hasStarted() && $request->get('protection_type') === 'advanced') {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors(['protection_type' => 'Only Quick Start protection is available after round start.']);
        }

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
            'protection_type' => 'in:advanced,quick',
            'realm_type' => 'in:random,join_pack,create_pack',
            'pack_name' => ('string|min:3|max:50|' . ($request->get('realm_type') !== 'random' ? 'required_if:realm,join_pack,create_pack' : 'nullable')),
            'pack_password' => ('string|min:3|max:50|' . ($request->get('realm_type') !== 'random' ? 'required_if:realm,join_pack,create_pack' : 'nullable')),
            'pack_size' => "integer|min:2|max:{$round->pack_size}|required_if:realm,create_pack",
            'discord' => 'in:yes,no',
        ]);

        /** @var Realm $realm */
        $realm = null;

        /** @var Dominion $dominion */
        $dominion = null;

        /** @var string $dominionName */
        $dominionName = null;

        try {
            DB::transaction(function () use ($request, $round, &$realm, &$dominion, &$dominionName) {
                $realmAssignmentService = app(RealmAssignmentService::class);
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
                        $useDiscord = true;
                        if ($request->get('discord') == 'no') {
                            $useDiscord = false;
                        }
                        $realm = $realmAssignmentService->findRealm($round, $race, $user, $useDiscord);
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
                        $realm = $realmAssignmentService->findRealm($round, $race, $user);
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
                    ($request->get('protection_type') ?: 'quick'),
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

                if ($dominion->pack_id === null && $request->get('discord') == 'no') {
                    $dominion->settings = ['usediscord' => false];
                    $dominion->save();
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
