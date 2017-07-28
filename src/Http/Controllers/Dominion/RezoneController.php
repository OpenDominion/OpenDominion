<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Exception;
use OpenDominion\Contracts\Calculators\Dominion\Actions\RezoningCalculator;
use OpenDominion\Contracts\Calculators\Dominion\LandCalculator;
use OpenDominion\Contracts\Services\Actions\RezoneActionService;
use OpenDominion\Contracts\Services\AnalyticsService;
use OpenDominion\Exceptions\BadInputException;
use OpenDominion\Exceptions\DominionLockedException;
use OpenDominion\Exceptions\NotEnoughResourcesException;
use OpenDominion\Http\Requests\Dominion\Actions\RezoneActionRequest;
use OpenDominion\Services\AnalyticsService\Event;

class RezoneController extends AbstractDominionController
{
    public function getRezone()
    {
        return view('pages.dominion.rezone', [
            'landCalculator' => app(LandCalculator::class),
            'rezoningCalculator' => app(RezoningCalculator::class),
        ]);
    }

    public function postRezone(RezoneActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $rezoneActionService = app(RezoneActionService::class);

        try {
            $result = $rezoneActionService->rezone(
                $dominion,
                $request->get('remove'),
                $request->get('add')
            );

        } catch (DominionLockedException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors(['Re-zoning land was not done due to the dominion being locked.']);

        } catch (BadInputException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);

        } catch (NotEnoughResourcesException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);

        } catch (Exception $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors(['Something went wrong. Please try again later.']);
        }

        $message = sprintf(
            'Your land has been re-zoned at a cost of %s platinum.',
            number_format($result['platinumCost'])
        );

        // todo: fire laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new Event(
            'dominion',
            'rezone',
            '', // todo: make null?
            array_sum($request->get('remove'))
        ));

        $request->session()->flash('alert-success', $message);
        return redirect()->route('dominion.rezone');
    }
}
