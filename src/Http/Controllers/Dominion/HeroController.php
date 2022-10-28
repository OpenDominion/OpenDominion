<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\HeroCalculator;
//use OpenDominion\Calculators\Dominion\LandCalculator;
//use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Helpers\HeroHelper;
use OpenDominion\Http\Requests\Dominion\Actions\HeroCreateActionRequest;
use OpenDominion\Models\Dominion;
//use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Traits\DominionGuardsTrait;

class HeroController extends AbstractDominionController
{
    use DominionGuardsTrait;

    public function getHeroes()
    {
        //$guardMembershipService = app(GuardMembershipService::class);
        $heroCalculator = app(HeroCalculator::class);
        $heroHelper = app(HeroHelper::class);
        //$landCalculator = app(LandCalculator::class);
        //$networthCalculator = app(NetworthCalculator::class);
        $protectionService = app(ProtectionService::class);
        //$rangeCalculator = app(RangeCalculator::class);

        $heroes = $this->getSelectedDominion()->heroes;

        return view('pages.dominion.heroes', compact(
            //'guardMembershipService',
            'heroCalculator',
            'heroHelper',
            //'landCalculator',
            //'networthCalculator',
            'protectionService',
            //'rangeCalculator',
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
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $dominion->heroes()->create([
            'name' => $request->get('name'),
            'class' => $request->get('class'),
            'trade' => $request->get('trade')
        ]);

        $request->session()->flash('alert-success', 'Your hero has been created!');
        return redirect()->route('dominion.heroes');
    }
}
