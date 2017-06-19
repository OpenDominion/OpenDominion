<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Exception;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Exceptions\BadInputException;
use OpenDominion\Exceptions\DominionLockedException;
use OpenDominion\Exceptions\NotEnoughResourcesException;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Http\Requests\Dominion\Actions\ExploreActionRequest;
use OpenDominion\Services\Actions\ExplorationActionService;
use OpenDominion\Services\AnalyticsService;
use OpenDominion\Services\DominionQueueService;

class ExplorationController extends AbstractDominionController
{
    public function getExplore()
    {
        $landHelper = app(LandHelper::class);
        $landCalculator = app(LandCalculator::class);
        $dominionQueueService = app(DominionQueueService::class);
        $dominionQueueService->setDominion($this->getSelectedDominion());

        return view('pages.dominion.explore', compact(
            'landHelper',
            'landCalculator',
            'dominionQueueService'
        ));
    }

    public function postExplore(ExploreActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $explorationActionService = app(ExplorationActionService::class);

        try {
            $result = $explorationActionService->explore($dominion, $request->get('explore'));

        } catch (DominionLockedException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors(['Exploration was not begun due to the dominion being locked.']);

        } catch (BadInputException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors(['Exploration was not begun due to bad input.']);

        } catch (NotEnoughResourcesException $e) {
            $totalLandToExplore = array_sum($request->get('explore'));

            return redirect()->back()
                ->withInput($request->all())
                ->withErrors(["You do not have enough platinum and/or draftees to explore for {$totalLandToExplore} acres."]);

        } catch (Exception $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors(['Something went wrong. Please try again later.']);
        }

        $message = sprintf(
            'Exploration begun at a cost of %s platinum and %s draftees. Your orders for exploration disheartens the military, and morale drops %s%%.',
            number_format($result['platinumCost']),
            number_format($result['drafteeCost']),
            number_format($result['moraleDrop'])
        );

        // todo: fire laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsService\Event(
            'dominion',
            'explore',
            '',
            array_sum($request->get('explore'))
        ));

        $request->session()->flash('alert-success', $message);
        return redirect()->route('dominion.explore');
    }
}
