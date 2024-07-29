<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\HeroHelper;
use OpenDominion\Http\Requests\Dominion\Actions\HeroCreateActionRequest;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Traits\DominionGuardsTrait;

class HeroController extends AbstractDominionController
{
    use DominionGuardsTrait;

    public function getHeroes()
    {
        $heroCalculator = app(HeroCalculator::class);
        $heroHelper = app(HeroHelper::class);

        $heroes = $this->getSelectedDominion()->heroes;

        return view('pages.dominion.heroes', compact(
            'heroCalculator',
            'heroHelper',
            'heroes'
        ));
    }

    public function postCreateHero(HeroCreateActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();

        try {
            $this->guardLockedDominion($dominion);

            if (!$dominion->heroes->isEmpty()) {
                throw new GameException('You can only have one hero at a time.');
            }

            $dominion->heroes()->create([
                'name' => $request->get('name'),
                'class' => $request->get('class')
            ]);
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Your hero has been created!');
        return redirect()->route('dominion.heroes');
    }

    public function getRetireHeroes()
    {
        $heroCalculator = app(HeroCalculator::class);
        $heroHelper = app(HeroHelper::class);

        $heroes = $this->getSelectedDominion()->heroes;

        return view('pages.dominion.retire', compact(
            'heroCalculator',
            'heroHelper',
            'heroes'
        ));
    }

    public function postRetireHeroes(HeroCreateActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();

        try {
            $this->guardLockedDominion($dominion);

            if ($dominion->heroes->isEmpty()) {
                throw new GameException('You do not have a hero to retire.');
            }

            $dominion->hero()->update([
                'name' => $request->get('name'),
                'class' => $request->get('class'),
                'experience' => (int) min($dominion->hero->experience, 10000) / 2
            ]);
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Your hero has been retired!');
        return redirect()->route('dominion.heroes');
    }
}
