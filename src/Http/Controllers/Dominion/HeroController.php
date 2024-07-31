<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\HeroHelper;
use OpenDominion\Http\Requests\Dominion\Actions\HeroBonusActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\HeroCreateActionRequest;
use OpenDominion\Services\Dominion\Actions\HeroActionService;
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

    public function postHeroes(HeroBonusActionRequest $request)
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

    public function getRetireHero()
    {
        $heroCalculator = app(HeroCalculator::class);
        $heroHelper = app(HeroHelper::class);

        $hero = $this->getSelectedDominion()->hero;

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
}
