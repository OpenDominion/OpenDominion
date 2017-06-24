<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Exceptions\BadInputException;
use OpenDominion\Exceptions\DominionLockedException;
use OpenDominion\Exceptions\NotEnoughResourcesException;
use OpenDominion\Interfaces\Calculators\Dominion\LandCalculatorInterface;
use OpenDominion\Interfaces\Services\Actions\RezoneActionServiceInterface;
use OpenDominion\Services\AnalyticsService;

class RezoneController extends AbstractDominionController
{
    protected $rezoneActionService;
    protected $landCalculator;

    /**
     * RezoneController constructor.
     *
     * @param \OpenDominion\Interfaces\Services\Actions\RezoneActionServiceInterface $rezoneActionService
     */
    public function __construct(RezoneActionServiceInterface $rezoneActionService)
    {
        $this->rezoneActionService = $rezoneActionService;
    }

    public function getRezone(Request $request, LandCalculatorInterface $landCalculator)
    {
        // @TODO: add selected dominion to request through middleware.
        $dominion = $this->getSelectedDominion();
        $rezoningPlatinumCost = $landCalculator->getRezoningPlatinumCost($dominion);
        $canAfford = floor($dominion->resource_platinum / $rezoningPlatinumCost);
        $barrenLand = $landCalculator->getBarrenLandByLandType();

        return view('pages.dominion.rezone', compact('dominion', 'rezoningPlatinumCost', 'canAfford', 'barrenLand'));
    }

    public function postRezone(Request $request, RezoneActionServiceInterface $rezoneActionService)
    {
        $dominion = $this->getSelectedDominion();

        try {
            $result = $rezoneActionService->rezone($dominion, $request->get('remove'), $request->get('add'));
        } catch (DominionLockedException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors(['Re-zoning was not done due to the dominion being locked.']);
        } catch (BadInputException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        } catch (NotEnoughResourcesException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors(['Something went wrong. Please try again later.']);
        }

        if ($result) {
            $message = sprintf('Your land has been re-zoned at a cost of %s platinum.', number_format($result));

            // todo: fire laravel event
            $analyticsService = app(AnalyticsService::class);
            $analyticsService->queueFlashEvent(new AnalyticsService\Event(
                'dominion',
                'rezone',
                '',
                $result
            ));

            $request->session()->flash('alert-success', $message);
        }
        return redirect()->route('dominion.rezone');
    }
}
