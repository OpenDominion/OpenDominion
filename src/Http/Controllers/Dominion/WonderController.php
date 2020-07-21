<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\WonderHelper;
//use OpenDominion\Http\Requests\Dominion\Actions\WonderActionRequest;
use OpenDominion\Models\RoundWonder;
use OpenDominion\Models\Wonder;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
//use OpenDominion\Services\Dominion\Actions\WonderActionService;

class WonderController extends AbstractDominionController
{
    public function getWonders()
    {
        $dominion = $this->getSelectedDominion();

        return view('pages.dominion.wonders', [
            'wonders' => $dominion->round->wonders()->with(['realm', 'wonder'])->get(),
            //'wonderCalculator' => app(WonderCalculator::class),
            'wonderHelper' => app(WonderHelper::class),
        ]);
    }

    public function postWonders() {}
    /*
    public function postWonders(WonderActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $wonderActionService = app(WonderActionService::class);

        try {
            $result = $wonderActionService->unlock(
                $dominion,
                $request->get('key')
            );
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        // todo: fire laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsEvent(
            'dominion',
            'wonder',
            $request->get('key')
        ));

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.wonders');
    }
    */
}
