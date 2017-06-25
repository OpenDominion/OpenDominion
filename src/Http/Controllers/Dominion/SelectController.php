<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Exception;
use OpenDominion\Contracts\Services\AnalyticsService;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\AnalyticsService\Event;
use OpenDominion\Services\Dominion\SelectorService;

class SelectController extends AbstractDominionController
{
    public function postSelect(Dominion $dominion)
    {
        $dominionSelectorService = app(SelectorService::class);

        try {
            $dominionSelectorService->selectUserDominion($dominion);

        } catch (Exception $e) {
            return response('Unauthorized', 401);
        }

        // todo: fire laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new Event(
            'dominion',
            'select'
        ));

        return redirect()->route('dominion.status');
    }
}
