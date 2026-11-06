<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\HeroHelper;
use OpenDominion\HeroCombat\Presentation\BattlePresenter;
use OpenDominion\HeroCombat\Registry\CombatRegistry;
use OpenDominion\Http\Requests\Dominion\Actions\HeroBattleOrdersActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\HeroCreateActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\HeroLoadoutActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\HeroUpgradeActionRequest;
use OpenDominion\Models\Hero;
use OpenDominion\Models\HeroBattle;
use OpenDominion\Models\HeroCombatant;
use OpenDominion\Models\HeroTournament;
use OpenDominion\Services\Dominion\Actions\HeroActionService;
use OpenDominion\Services\Dominion\HeroBattleService;
use OpenDominion\Services\Dominion\HeroTournamentService;
use OpenDominion\Traits\DominionGuardsTrait;

class HeroController extends AbstractDominionController
{
    use DominionGuardsTrait;

    public function getHeroes()
    {
        $heroCalculator = app(HeroCalculator::class);
        $heroHelper = app(HeroHelper::class);

        $hero = $this->getSelectedDominion()->hero;
        $heroClassData = collect($hero !== null ? $hero->class_data : [])->keyBy('key');

        return view('pages.dominion.heroes', compact(
            'heroCalculator',
            'heroHelper',
            'hero',
            'heroClassData',
        ));
    }

    public function postHeroes(HeroUpgradeActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $heroActionService = app(HeroActionService::class);

        try {
            $result = $heroActionService->unlock(
                $dominion,
                $request->get('key')
            );
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.heroes');
    }

    public function postLoadout(HeroLoadoutActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $heroActionService = app(HeroActionService::class);

        try {
            $result = $heroActionService->updateLoadout(
                $dominion,
                $request->input('technique'),
                $request->input('ability'),
                $request->input('gear', [])
            );
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.heroes');
    }

    public function postCreateHero(HeroCreateActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $heroActionService = app(HeroActionService::class);

        try {
            $result = $heroActionService->create(
                $dominion,
                $request->get('name'),
                $request->get('class')
            );
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.heroes');
    }

    public function getChangeClass(Request $request, string $class)
    {
        $heroCalculator = app(HeroCalculator::class);
        $heroHelper = app(HeroHelper::class);

        $hero = $this->getSelectedDominion()->hero;

        if ($hero === null) {
            $request->session()->flash('alert-warning', 'You do not have a hero to change class.');
            return redirect()->back();
        }

        // Validate the target class exists
        $heroClasses = $heroHelper->getClasses()->keyBy('key');
        $targetClass = $heroClasses[$class] ?? null;
        if ($targetClass === null) {
            $request->session()->flash('alert-danger', 'Invalid hero class selected.');
            return redirect()->route('dominion.heroes');
        }

        // Check cooldown
        if (!$heroCalculator->canChangeClass($hero)) {
            $hoursRemaining = $heroCalculator->hoursUntilClassChange($hero);
            $request->session()->flash('alert-warning', "You cannot change your hero class for another {$hoursRemaining} hours.");
            return redirect()->route('dominion.heroes');
        }

        return view('pages.dominion.change-class', compact(
            'heroCalculator',
            'heroHelper',
            'hero',
            'targetClass'
        ));
    }

    public function postChangeClass(Request $request, string $class)
    {
        $dominion = $this->getSelectedDominion();
        $heroActionService = app(HeroActionService::class);

        try {
            $result = $heroActionService->changeClass(
                $dominion,
                $class
            );
        } catch (GameException $e) {
            return redirect()->route('dominion.heroes')
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.heroes');
    }

    public function getBattles()
    {
        $heroCalculator = app(HeroCalculator::class);
        $heroHelper = app(HeroHelper::class);

        $dominion = $this->getSelectedDominion();
        $hero = $dominion->hero;
        if ($hero === null) {
            return redirect()->route('dominion.heroes');
        }

        $activeBattles = HeroBattle::query()
            ->controlledBy($dominion)
            ->with('combatants.hero', 'turns')
            ->active()
            ->orderByDesc('created_at')
            ->get();
        $inactiveBattles = HeroBattle::query()
            ->controlledBy($dominion)
            ->with('combatants')
            ->inactive()
            ->orderByDesc('created_at')
            ->get();

        if ($activeBattles->isEmpty() && $inactiveBattles->isNotEmpty()) {
            $activeBattles = collect([$inactiveBattles->first()->load('combatants.hero', 'turns')]);
        }

        $battlePresenter = app(BattlePresenter::class);
        $heroBattleService = app(HeroBattleService::class);
        $battleStatus = $heroBattleService->battleStatus($dominion);
        $pollForChanges = $heroBattleService->shouldPoll($dominion, $battleStatus);

        return view('pages.dominion.hero-battles', compact(
            'activeBattles',
            'inactiveBattles',
            'battlePresenter',
            'battleStatus',
            'pollForChanges',
            'heroCalculator',
            'heroHelper',
            'hero',
        ));
    }

    public function getBattleReport(HeroBattle $battle)
    {
        $heroCalculator = app(HeroCalculator::class);
        $heroHelper = app(HeroHelper::class);

        $dominion = $this->getSelectedDominion();
        if ($dominion->hero === null) {
            return redirect()->route('dominion.heroes');
        }

        $involved = $battle->combatants->contains(
            fn ($combatant) => $combatant->controller_dominion_id === $dominion->id || $combatant->dominion_id === $dominion->id
        );
        if (!$involved) {
            return redirect()->route('dominion.heroes.battles');
        }

        $view = app(BattlePresenter::class)->present($battle, $dominion->id);

        return view('pages.dominion.hero-battle-report', compact(
            'battle',
            'view',
            'heroCalculator',
            'heroHelper',
        ));
    }

    public function postBattles(Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $heroActionService = app(HeroActionService::class);

        try {
            $combatant = HeroCombatant::findOrFail($request->get('combatant'));
            $result = $heroActionService->updateCombatant(
                $dominion,
                $combatant,
                $request->get('strategy'),
                $request->get('automated') == 'on'
            );
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        return redirect()->route('dominion.heroes.battles');
    }

    public function postBattleOrders(HeroBattleOrdersActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $heroActionService = app(HeroActionService::class);
        $heroBattleService = app(HeroBattleService::class);

        $orders = [];
        foreach ($request->input('orders') as $combatantId => $order) {
            $orders[(int) $combatantId] = [
                'action' => $order['action'],
                'target' => isset($order['target']) && $order['target'] !== '' ? (int) $order['target'] : null,
            ];
        }

        try {
            $battle = HeroBattle::findOrFail($request->input('battle'));
            $turn = $battle->current_turn;
            $heroBattleService->checkTime($battle);
            $heroActionService->queueOrders($dominion, $battle->refresh(), $orders);
            $turnProcessed = $heroBattleService->processTurn($battle->refresh());
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        if ($turnProcessed) {
            $request->session()->flash('alert-success', "Turn {$turn} resolved.");
        } else {
            $request->session()->flash('alert-success', "Orders locked in. The turn resolves once every hero has acted.");
        }

        return redirect()->route('dominion.heroes.battles');
    }

    public function getDeleteCombatAction(Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $heroActionService = app(HeroActionService::class);
        $heroBattleService = app(HeroBattleService::class);

        try {
            $combatant = HeroCombatant::findOrFail($request->get('combatant'));
            $result = $heroActionService->dequeueAction(
                $dominion,
                $combatant,
                $request->get('action')
            );
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        return redirect()->route('dominion.heroes.battles');
    }

    public function getPracticeBattle(Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $heroCalculator = app(HeroCalculator::class);
        $heroHelper = app(HeroHelper::class);

        if ($dominion->hero === null) {
            return redirect()->route('dominion.heroes');
        }

        $encounters = collect(app(CombatRegistry::class)->encounters())->except('default');
        $companions = $encounters->contains(fn ($encounter) => $encounter->maxPartySize() > 1)
            ? app(HeroBattleService::class)->availableCompanions($dominion)
            : collect();

        return view('pages.dominion.hero-battle-practice', compact(
            'encounters',
            'companions',
            'heroCalculator',
            'heroHelper',
        ));
    }

    public function postPracticeBattle(Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $heroBattleService = app(HeroBattleService::class);

        try {
            $this->guardLockedDominion($dominion);
            $companions = [];
            if ($request->filled('companion_hero_id')) {
                $companion = $heroBattleService->availableCompanions($dominion)->firstWhere('id', (int) $request->get('companion_hero_id'));
                if ($companion === null) {
                    throw new GameException('You can only bring heroes from your own realm.');
                }
                $companions[] = $companion;
            }
            $result = $heroBattleService->createPracticeBattle($dominion, $request->get('encounter'), $companions);
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'The battle begins!');
        return redirect()->route('dominion.heroes.battles');
    }

    public function getJoinQueue(Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $heroBattleService = app(HeroBattleService::class);

        try {
            $this->guardLockedDominion($dominion);
            $result = $heroBattleService->joinQueue($dominion, $request->integer('size', 1));
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        if ($result === null) {
            $request->session()->flash('alert-success', 'You have joined the queue.');
        } else {
            $request->session()->flash('alert-success', 'The battle begins!');
        }
        return redirect()->route('dominion.heroes.battles');
    }

    public function getLeaveQueue(Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $heroBattleService = app(HeroBattleService::class);

        try {
            $this->guardLockedDominion($dominion);
            $result = $heroBattleService->leaveQueue($dominion);
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'You have left the queue.');
        return redirect()->route('dominion.heroes.battles');
    }

    public function getLeaderBoard()
    {
        $round_id = $this->getSelectedDominion()->round_id;
        $heroes = Hero::select('heroes.*')
            ->join('dominions', 'dominions.id', 'heroes.dominion_id')
            ->where('dominions.round_id', $round_id)
            ->orderByDesc('combat_rating')
            ->get()
            ->filter(function ($hero) {
                return $hero->stat_combat_wins || $hero->stat_combat_losses || $hero->stat_combat_draws;
            });

        return view('pages.dominion.hero-battle-leaderboard', compact(
            'heroes',
        ));
    }

    public function getTournaments()
    {
        $heroHelper = app(HeroHelper::class);
        $round_id = $this->getSelectedDominion()->round_id;
        $tournaments = HeroTournament::query()
            ->with('battles.combatants', 'battles.winner', 'participants.hero.dominion.realm')
            ->where('round_id', $round_id)
            ->orderByDesc('created_at')
            ->get();

        return view('pages.dominion.hero-tournaments', compact(
            'heroHelper',
            'tournaments',
        ));
    }

    public function getJoinTournament(Request $request, HeroTournament $tournament)
    {
        $dominion = $this->getSelectedDominion();
        $heroTournamentService = app(HeroTournamentService::class);

        try {
            $this->guardLockedDominion($dominion);
            $heroTournamentService->joinTournament($tournament, $dominion);
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        return redirect()->route('dominion.heroes.tournaments');
    }

    public function getLeaveTournament(Request $request, HeroTournament $tournament)
    {
        $dominion = $this->getSelectedDominion();
        $heroTournamentService = app(HeroTournamentService::class);

        try {
            $this->guardLockedDominion($dominion);
            $heroTournamentService->leaveTournament($tournament, $dominion);
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        return redirect()->route('dominion.heroes.tournaments');
    }
}
