<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\HeroHelper;
use OpenDominion\Http\Requests\Dominion\Actions\HeroCreateActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\HeroUpgradeActionRequest;
use OpenDominion\Models\Hero;
use OpenDominion\Models\HeroBattle;
use OpenDominion\Models\HeroCombatant;
use OpenDominion\Models\HeroTournament;
use OpenDominion\Services\Dominion\Actions\HeroActionService;
use OpenDominion\Services\Dominion\HeroBattleService;
use OpenDominion\Traits\DominionGuardsTrait;

class HeroController extends AbstractDominionController
{
    use DominionGuardsTrait;

    public function getHeroes()
    {
        $heroCalculator = app(HeroCalculator::class);
        $heroHelper = app(HeroHelper::class);

        $hero = $this->getSelectedDominion()->hero;

        return view('pages.dominion.heroes', compact(
            'heroCalculator',
            'heroHelper',
            'hero'
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

    public function getRetireHero(Request $request)
    {
        $heroCalculator = app(HeroCalculator::class);
        $heroHelper = app(HeroHelper::class);

        $hero = $this->getSelectedDominion()->hero;

        if ($hero === null) {
            $request->session()->flash('alert-warning', 'You do not have a hero to retire.');
            return redirect()->back();
        }

        return view('pages.dominion.retire', compact(
            'heroCalculator',
            'heroHelper',
            'hero'
        ));
    }

    public function postRetireHero(HeroCreateActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $heroActionService = app(HeroActionService::class);

        try {
            $result = $heroActionService->retire(
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

    public function getBattles()
    {
        $heroCalculator = app(HeroCalculator::class);
        $heroHelper = app(HeroHelper::class);

        $hero = $this->getSelectedDominion()->hero;
        if ($hero === null) {
            return redirect()->route('dominion.heroes');
        }

        $activeBattles = $hero->battles()
            ->with('combatants', 'winner', 'actions.combatant')
            ->active()
            ->orderByDesc('created_at')
            ->get();
        $inactiveBattles = $hero->battles()
            ->with('combatants', 'winner', 'actions.combatant')
            ->inactive()
            ->orderByDesc('created_at')
            ->get();

        if ($activeBattles->isEmpty() && $inactiveBattles->isNotEmpty()) {
            $activeBattles = collect([$inactiveBattles->first()]);
        }

        return view('pages.dominion.hero-battles', compact(
            'activeBattles',
            'inactiveBattles',
            'heroCalculator',
            'heroHelper',
            'hero',
        ));
    }

    public function getBattleReport(HeroBattle $battle)
    {
        $heroCalculator = app(HeroCalculator::class);
        $heroHelper = app(HeroHelper::class);

        $hero = $this->getSelectedDominion()->hero;
        if ($hero === null) {
            return redirect()->route('dominion.heroes');
        }

        if (!$battle->combatants->pluck('hero_id')->contains($hero->id)) {
            return redirect()->route('dominion.heroes.battles');
        }

        return view('pages.dominion.hero-battle-report', compact(
            'battle',
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

    public function getAddCombatAction(Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $heroActionService = app(HeroActionService::class);
        $heroBattleService = app(HeroBattleService::class);

        try {
            $action = $request->get('action');
            $combatant = HeroCombatant::findOrFail($request->get('combatant'));
            $heroBattleService->checkTime($combatant->battle);
            $result = $heroActionService->queueAction(
                $dominion,
                $combatant->refresh(),
                $action
            );
            $turnProcessed = $heroBattleService->processTurn($combatant->battle->refresh());
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        if ($turnProcessed) {
            $request->session()->flash('alert-success', "{$combatant->name} performed {$action}!");
        } else {
            $request->session()->flash('alert-success', "Queued action for {$combatant->name}: {$action}.");
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
        $heroBattleService = app(HeroBattleService::class);

        try {
            $this->guardLockedDominion($dominion);
            $result = $heroBattleService->createPracticeBattle($dominion);
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
            $result = $heroBattleService->joinQueue($dominion);
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
        $round_id = $this->getSelectedDominion()->round_id;
        $tournaments = HeroTournament::query()
            ->with('battles.combatants', 'battles.winner', 'participants.hero.dominion.realm')
            ->where('round_id', $round_id)
            ->orderByDesc('created_at')
            ->get();

        return view('pages.dominion.hero-tournaments', compact(
            'tournaments',
        ));
    }
}
